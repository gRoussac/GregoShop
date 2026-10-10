<?php
/**
 * For the full copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
?>

<div id="contentInfosBlock" class="modules-step">
  <header class="modules-step__header">
    <h2><?php echo $this->translator->trans('Installation of modules', [], 'Install'); ?></h2>
  </header>

  <?php if (count($this->themes) === 1): ?>
    <input type="hidden" value="<?php echo current($this->themes)->get('name'); ?>" name="theme" />
  <?php else: ?>
    <div class="field clearfix theme-selection">
      <label class="aligned">
        <?php echo $this->translator->trans('Installation of theme', [], 'Install'); ?>
        <p class="theme-infos userInfos aligned"><?php echo $this->translator->trans('Select the theme to install', [], 'Install'); ?></p>
      </label>

      <div class="contentinput">
        <div class="themes-container">
          <?php foreach ($this->themes as $theme): ?>
            <div class="theme-card">
              <label>
                <input value="<?php echo $theme->get('name'); ?>" type="radio" name="theme" style="vertical-align: top;" <?php if ($this->session->content_theme === $theme->get('name')): ?>checked="checked"<?php endif ?> autocomplete="off" />
                <?php echo $theme->get('display_name'); ?>
                <div class="image-block">
                  <img src="../<?php echo $theme->get('preview'); ?>" alt="<?php echo $theme->get('display_name'); ?>">
                </div>

                <p class="theme-version">
                  <strong><?php echo $this->translator->trans('Version:', [], 'Install'); ?></strong> <?php echo $theme->get('version'); ?>
                </p>
              </label>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <ul class="modules-mode-list">
    <li>
      <label>
        <input value="none" name="module-action" type="radio" autocomplete="off"<?php if ($this->moduleAction === static::MODULES_NONE): ?> checked="checked"<?php endif; ?>/>
        <span><?php echo $this->translator->trans('Install no modules (recommended)', [], 'Install') ?></span>
      </label>
    </li>
    <li>
      <label>
        <input value="all" name="module-action" type="radio" autocomplete="off"<?php if ($this->moduleAction === static::MODULES_ALL): ?> checked="checked"<?php endif; ?>/>
        <span><?php echo $this->translator->trans('Install all modules', [], 'Install') ?></span>
      </label>
    </li>
    <li>
      <label>
        <input value="selected" name="module-action" type="radio" autocomplete="off" <?php if ($this->moduleAction === static::MODULES_SELECTED): ?> checked="checked"<?php endif; ?>/>
        <span><?php echo $this->translator->trans('Select the modules to install', [], 'Install') ?></span>
      </label>
    </li>
    <li>
      <label>
        <input value="bo" name="module-action" type="radio" autocomplete="off" <?php if ($this->moduleAction === static::MODULES_BO_ONLY): ?> checked="checked"<?php endif; ?>/>
        <span><?php echo $this->translator->trans('Back-office modules only (Administration)', [], 'Install') ?></span>
      </label>
    </li>
    <li>
      <label>
        <input value="fo" name="module-action" type="radio" autocomplete="off" <?php if ($this->moduleAction === static::MODULES_FO_ONLY): ?> checked="checked"<?php endif; ?>/>
        <span><?php echo $this->translator->trans('Front-office modules only (exclude Administration)', [], 'Install') ?></span>
      </label>
    </li>
  </ul>

  <div id="modules-container">
    <?php $selectAllPlaced = false; ?>
    <?php foreach ($this->getModulesPerCategories() as $category): ?>
      <?php if (empty($category->modules)): ?>
        <?php continue; ?>
      <?php endif; ?>
      <section class="modules-category">
        <div class="modules-category__head">
          <h3 class="modules-category__title"><?php echo $this->translator->trans($category->name, [], 'Install'); ?></h3>
          <?php if (!$selectAllPlaced): ?>
            <label class="modules-step__select-all">
              <input type="checkbox" name="select-all"<?php if ($this->selectAllButton): ?> checked="checked"<?php endif; ?>>
              <span><?php echo $this->translator->trans('Select all', [], 'Install'); ?></span>
            </label>
            <?php $selectAllPlaced = true; ?>
          <?php endif; ?>
        </div>
        <ul class="modules-grid">
          <?php foreach ($category->modules as $module): ?>
            <li>
              <label>
                <input value="<?php echo $module->get('name') ?>" type="checkbox" name="modules[]" <?php if ($this->session->content_modules === null || is_array($this->session->content_modules) && in_array($module->get('name'), $this->session->content_modules)): ?>checked="checked"<?php endif; ?> autocomplete="off" />
                <span><?php echo $module->get('displayName') ?></span>
              </label>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endforeach; ?>
  </div>
</div>
