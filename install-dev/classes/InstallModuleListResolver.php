<?php
/**
 * For the full copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

use PrestaShop\PrestaShop\Core\Util\ArrayFinder;
use PrestaShop\PrestaShop\Core\Util\File\YamlParser;

/**
 * Resolves which module names to install for HTTP content modes and CLI --modules.
 */
class InstallModuleListResolver
{
    public const MODE_ALL = 'all';
    public const MODE_NONE = 'none';
    public const MODE_BO = 'bo';
    public const MODE_FO = 'fo';

    private const CATEGORY_ADMINISTRATION = 'Administration';

    /**
     * @var array<string, string>|null
     */
    private ?array $parentCategoryByTab = null;

    /**
     * @param list<ArrayFinder> $modulesOnDisk
     *
     * @return list<string>
     */
    public function resolveFromHttpAction(int $moduleAction, array $modulesOnDisk, array $selectedModules = []): array
    {
        // All HTTP modes show the checkbox list: trust posted modules[].
        // Radios only set the default checks (see defaultSelectedModuleNames()).
        $picked = array_values(array_filter(
            $selectedModules,
            static fn ($name) => is_string($name) && $name !== ''
        ));

        if ($moduleAction === InstallControllerHttpContent::MODULES_ALL && $picked === []) {
            // No modules[] in POST (non-UI caller): install everything.
            return $this->allNames($modulesOnDisk);
        }

        return $picked;
    }

    /**
     * Default checked modules for "Select the modules to install".
     * Config: install-dev/data/module_selection_defaults.php
     *
     * @param list<ArrayFinder> $modulesOnDisk
     *
     * @return list<string>
     */
    public function defaultSelectedModuleNames(array $modulesOnDisk): array
    {
        $exclude = array_fill_keys($this->selectedExcludeNames(), true);
        $names = [];
        foreach ($this->allNames($modulesOnDisk) as $name) {
            if (!isset($exclude[$name])) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * @return list<string>
     */
    public function selectedExcludeNames(): array
    {
        $exclude = $this->selectionDefaultsConfig()['selected_exclude'] ?? [];
        if (!is_array($exclude)) {
            return [];
        }

        $names = [];
        foreach ($exclude as $name) {
            if (is_string($name) && $name !== '') {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * Module folder name → install list category key (e.g. other).
     *
     * @return array<string, string>
     */
    public function categoryOverrides(): array
    {
        $overrides = $this->selectionDefaultsConfig()['category_overrides'] ?? [];
        if (!is_array($overrides)) {
            return [];
        }

        $map = [];
        foreach ($overrides as $moduleName => $categoryKey) {
            if (
                is_string($moduleName)
                && $moduleName !== ''
                && is_string($categoryKey)
                && $categoryKey !== ''
            ) {
                $map[$moduleName] = $categoryKey;
            }
        }

        return $map;
    }

    /**
     * @return array<string, mixed>
     */
    private function selectionDefaultsConfig(): array
    {
        $path = _PS_INSTALL_PATH_ . 'data/module_selection_defaults.php';
        if (!is_file($path)) {
            return [];
        }

        /** @var mixed $config */
        $config = require $path;

        return is_array($config) ? $config : [];
    }

    /**
     * CLI --modules value: empty/none, all, bo, fo, or comma-separated names.
     *
     * @param list<ArrayFinder> $modulesOnDisk
     *
     * @return list<string>
     */
    public function resolveFromCliSpec(string $spec, array $modulesOnDisk): array
    {
        $normalized = strtolower(trim($spec));
        if ($normalized === '' || $normalized === self::MODE_NONE) {
            return [];
        }

        if ($normalized === self::MODE_ALL) {
            return $this->allNames($modulesOnDisk);
        }

        if ($normalized === self::MODE_BO) {
            return $this->namesInAdministration($modulesOnDisk);
        }

        if ($normalized === self::MODE_FO) {
            return array_values(array_diff(
                $this->allNames($modulesOnDisk),
                $this->namesInAdministration($modulesOnDisk)
            ));
        }

        $names = [];
        foreach (explode(',', $spec) as $name) {
            $name = trim($name);
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * @param list<ArrayFinder> $modulesOnDisk
     *
     * @return list<string>
     */
    public function namesInAdministration(array $modulesOnDisk): array
    {
        $names = [];
        foreach ($modulesOnDisk as $module) {
            if ($this->parentCategoryName($module) === self::CATEGORY_ADMINISTRATION) {
                $names[] = $module->get('name');
            }
        }

        return $names;
    }

    /**
     * @param list<ArrayFinder> $modulesOnDisk
     *
     * @return list<string>
     */
    private function allNames(array $modulesOnDisk): array
    {
        $names = [];
        foreach ($modulesOnDisk as $module) {
            $names[] = $module->get('name');
        }

        return $names;
    }

    private function parentCategoryName(ArrayFinder $module): string
    {
        $tab = $module->get('tab');
        if (!is_string($tab) || $tab === '') {
            return '';
        }

        $map = $this->getParentCategoryByTabMap();
        if (isset($map[$tab])) {
            return $map[$tab];
        }

        // Direct parent tab match (e.g. administration on Administration).
        foreach ($map as $childTab => $parentName) {
            if ($childTab === $tab) {
                return $parentName;
            }
        }

        $yamlParser = new YamlParser(_PS_CACHE_DIR_);
        $config = $yamlParser->parse(_PS_ROOT_DIR_ . '/app/config/addons/categories.yml');
        foreach ($config['prestashop']['addons']['categories'] ?? [] as $parentCategory) {
            $parentName = $parentCategory['name'] ?? null;
            $parentTab = $parentCategory['tab'] ?? null;
            if (is_string($parentName) && is_string($parentTab) && $parentTab === $tab) {
                return $parentName;
            }
        }

        return '';
    }

    /**
     * @return array<string, string>
     */
    private function getParentCategoryByTabMap(): array
    {
        if ($this->parentCategoryByTab !== null) {
            return $this->parentCategoryByTab;
        }

        $this->parentCategoryByTab = [];
        $yamlParser = new YamlParser(_PS_CACHE_DIR_);
        $config = $yamlParser->parse(_PS_ROOT_DIR_ . '/app/config/addons/categories.yml');

        // First parent wins (same as CategoriesProvider). Tabs like "administration"
        // appear under several parents later in the YAML; last-write would mis-class
        // BO modules as Shipping / FO.
        foreach ($config['prestashop']['addons']['categories'] ?? [] as $parentCategory) {
            $parentName = $parentCategory['name'] ?? null;
            if (!is_string($parentName) || $parentName === '') {
                continue;
            }
            foreach ($parentCategory['categories'] ?? [] as $childCategory) {
                $childTab = $childCategory['tab'] ?? null;
                if (
                    is_string($childTab)
                    && $childTab !== ''
                    && !isset($this->parentCategoryByTab[$childTab])
                ) {
                    $this->parentCategoryByTab[$childTab] = $parentName;
                }
            }
        }

        return $this->parentCategoryByTab;
    }
}
