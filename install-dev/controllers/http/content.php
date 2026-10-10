<?php
/**
 * For the full copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

use PrestaShopBundle\Install\Install;
use PrestaShop\PrestaShop\Core\Addon\Theme\Theme;
use PrestaShop\PrestaShop\Core\Util\ArrayFinder;
use PrestaShop\PrestaShop\Core\Util\File\YamlParser;
use PrestaShopBundle\Service\DataProvider\Admin\CategoriesProvider;

/**
 * Step 5: configure content
 */
class InstallControllerHttpContent extends InstallControllerHttp implements HttpConfigureInterface
{
    public const MODULES_ALL = 0;
    public const MODULES_SELECTED = 1;
    public const MODULES_NONE = 2;
    public const MODULES_BO_ONLY = 3;
    public const MODULES_FO_ONLY = 4;

    /** @var array<string, int> Form tokens (never use bare 0 — empty HTML value). */
    public const MODULE_ACTION_TOKENS = [
        'all' => self::MODULES_ALL,
        'selected' => self::MODULES_SELECTED,
        'none' => self::MODULES_NONE,
        'bo' => self::MODULES_BO_ONLY,
        'fo' => self::MODULES_FO_ONLY,
    ];

    /**
     * Modules present on the disk
     *
     * @var array
     */
    public $modules = [];

    /**
     * Themes present on the disk
     *
     * @var array
     */
    public $themes = [];

    /**
     * Define the current action for modules
     *
     * @var int
     */
    public $moduleAction = self::MODULES_NONE;

    /**
     * Define if the select all modules is selected
     *
     * @var bool
     */
    public $selectAllButton = false;

    /**
     * Parent category name by module tab (from addons categories.yml).
     *
     * @var array<string, string>|null
     */
    private $parentCategoryByTab = null;

    public function init(): void
    {
        $this->model = new Install();
        $this->modules = $this->model->getModulesOnDisk();
        $this->themes = $this->model->getThemesOnDisk();
        // Empty-shop install: no demonstration catalog is shipped.
        $this->session->content_install_fixtures = false;
    }

    /**
     * {@inheritdoc}
     */
    public function processNextStep(): void
    {
        $moduleAction = $this->resolveModuleActionFromRequest();

        $selected = Tools::getValue('modules', []);
        $selectedModules = is_array($selected) ? $selected : [];
        $this->session->content_modules = (new InstallModuleListResolver())->resolveFromHttpAction(
            $moduleAction,
            $this->modules,
            $selectedModules
        );
        // Store token string, never bare int 0 (MODULES_ALL): session/cookie empty() footguns.
        $this->session->moduleAction = static::moduleActionToToken($moduleAction);
        $theme = Tools::getValue('theme');
        if ($theme === false || $theme === null || $theme === '') {
            $theme = $this->session->content_theme;
        }
        if ($theme === false || $theme === null || $theme === '') {
            $theme = Theme::getDefaultTheme();
        }
        $this->session->content_theme = $theme;
        $this->session->content_install_fixtures = false;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): bool
    {
        return !empty($this->session->content_theme);
    }

    /**
     * {@inheritdoc}
     */
    public function display(): void
    {
        if ($this->session->content_theme === null) {
            foreach ($this->themes as $theme) {
                if ($theme->get('name') === Theme::getDefaultTheme()) {
                    $this->session->content_theme = $theme->get('name');
                    break;
                }
            }
        }

        $this->moduleAction = $this->moduleActionFromSession();
        $this->selectAllButton = $this->session->content_modules === null || count($this->modules) === count($this->session->content_modules);

        $this->displayContent('content');
    }

    public function getModulesPerCategories(): array
    {
        $yamlParser = new YamlParser(_PS_CACHE_DIR_);
        $prestashopAddonsConfig = $yamlParser->parse(_PS_ROOT_DIR_ . '/app/config/addons/categories.yml');
        $categoriesProvider = new CategoriesProvider(
            $prestashopAddonsConfig['prestashop']['addons']['categories'],
            []
        );

        $categories = $categoriesProvider->getCategories()['categories']->subMenu;
        foreach ($this->modules as $module) {
            $tab = $this->findModuleCategory($module, $categories);
            $categories[$tab]->modules[] = $module;
        }

        foreach ($categories as $category) {
            uasort($category->modules, [$this, 'sortModulesByDisplayname']);
        }

        return $categories;
    }

    protected function sortModulesByDisplayName(ArrayFinder $a, ArrayFinder $b): int
    {
        return $a->get('displayName') <=> $b->get('displayName');
    }

    protected function findModuleCategory(ArrayFinder $module, array $categories)
    {
        $moduleName = $module->get('name');
        if (is_string($moduleName) && $moduleName !== '') {
            $overrides = (new InstallModuleListResolver())->categoryOverrides();
            $override = $overrides[$moduleName] ?? null;
            if (is_string($override) && isset($categories[$override])) {
                return $override;
            }
        }

        $tab = $module->get('tab');
        if (!empty($tab)) {
            foreach ($categories as $category) {
                if ($tab === $category->tab) {
                    return $category->name;
                }
            }

            $parentName = $this->findParentCategoryNameByTab((string) $tab);
            if ($parentName !== null && isset($categories[$parentName])) {
                return $parentName;
            }
            foreach ($categories as $category) {
                if ($parentName === $category->name) {
                    return $category->name;
                }
            }
        }

        return CategoriesProvider::CATEGORY_OTHER;
    }

    /**
     * @return list<int>
     */
    private function getAllowedModuleActions(): array
    {
        return [
            static::MODULES_ALL,
            static::MODULES_SELECTED,
            static::MODULES_NONE,
            static::MODULES_BO_ONLY,
            static::MODULES_FO_ONLY,
        ];
    }

    /**
     * Map POST module-action (token or legacy int) to MODULES_* constant.
     * Default: NONE. Do not cast missing/empty to 0 (that is MODULES_ALL).
     */
    public function resolveModuleActionFromRequest(): int
    {
        $raw = Tools::getValue('module-action', 'none');
        if ($raw === false || $raw === null || $raw === '') {
            return static::MODULES_NONE;
        }

        return $this->coerceModuleAction($raw);
    }

    /**
     * Read session moduleAction (token string or legacy int). Default NONE.
     */
    private function moduleActionFromSession(): int
    {
        $sessionAction = $this->session->moduleAction;
        if ($sessionAction === null || $sessionAction === '' || $sessionAction === false) {
            return static::MODULES_NONE;
        }

        return $this->coerceModuleAction($sessionAction);
    }

    /**
     * @param mixed $raw
     */
    private function coerceModuleAction($raw): int
    {
        if (is_string($raw)) {
            $token = strtolower(trim($raw));
            if (isset(static::MODULE_ACTION_TOKENS[$token])) {
                return static::MODULE_ACTION_TOKENS[$token];
            }
            if (!is_numeric($token)) {
                return static::MODULES_NONE;
            }
            $raw = $token;
        }

        if (is_numeric($raw)) {
            $asInt = (int) $raw;
            if (in_array($asInt, $this->getAllowedModuleActions(), true)) {
                return $asInt;
            }
        }

        return static::MODULES_NONE;
    }

    public static function moduleActionToToken(int $moduleAction): string
    {
        $flip = array_flip(self::MODULE_ACTION_TOKENS);

        return $flip[$moduleAction] ?? 'none';
    }

    private function findParentCategoryNameByTab(string $tab): ?string
    {
        $map = $this->getParentCategoryByTabMap();

        return $map[$tab] ?? null;
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
        $prestashopAddonsConfig = $yamlParser->parse(_PS_ROOT_DIR_ . '/app/config/addons/categories.yml');
        $addonsCategories = $prestashopAddonsConfig['prestashop']['addons']['categories'] ?? [];

        // First parent wins (CategoriesProvider order). Do not let later YAML
        // parents overwrite tabs such as "administration".
        foreach ($addonsCategories as $parentCategory) {
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
