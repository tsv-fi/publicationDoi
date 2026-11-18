<?php

/**
 * @file plugins/generic/publicationDoi/PublicationDoiPlugin.php
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2003-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublicationDoiPlugin
 *
 * @ingroup plugins_generic_publicationDoi
 *
 * @brief Adds a "DOIs" tab to the publication view of the workflow (OJS, OMP, OPS 3.4)
 *  that lists the DOIs of the publication and its related objects. Read-only: the
 *  DOIs come from the publication data the workflow page has already loaded.
 */

namespace APP\plugins\generic\publicationDoi;

use APP\core\Application;
use APP\template\TemplateManager;
use PKP\context\Context;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class PublicationDoiPlugin extends GenericPlugin
{
    /** State key read by templates/publicationDoiTab.tpl */
    public const STATE_KEY = 'publicationDoiData';

    public const TYPE_PUBLICATION = 'publication';
    public const TYPE_REPRESENTATION = 'representation';
    public const TYPE_CHAPTER = 'chapter'; // OMP
    public const TYPE_SUBMISSION_FILE = 'file'; // OMP

    /** Set during TemplateManager::display when the tab should be rendered */
    protected bool $showTab = false;

    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        if ($success && $this->getEnabled()) {
            Hook::add('TemplateManager::display', [$this, 'handleTemplateDisplay']);
            Hook::add('Template::Workflow::Publication', [$this, 'addPublicationTab']);
        }
        return $success;
    }

    /**
     * @copydoc Plugin::isSitePlugin()
     *
     * Enabled for the whole site. Whether the tab is shown is decided per
     * context from its DOI settings, see getEnabledTypes().
     */
    public function isSitePlugin()
    {
        return true;
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.generic.publicationDoi.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription()
    {
        return __('plugins.generic.publicationDoi.description');
    }

    /**
     * DOI types of the publication and its related objects that are enabled in
     * the context. Empty when the tab should not be shown: DOIs disabled or no
     * registration agency selected.
     *
     * @return string[]
     */
    public static function getEnabledTypes(Context $context): array
    {
        if (!$context->areDoisEnabled() || $context->getConfiguredDoiAgency() === null) {
            return [];
        }

        $supportedTypes = [self::TYPE_PUBLICATION, self::TYPE_REPRESENTATION];
        if (Application::get()->getName() === 'omp') {
            $supportedTypes[] = self::TYPE_CHAPTER;
            $supportedTypes[] = self::TYPE_SUBMISSION_FILE;
        }

        return array_values(array_intersect($supportedTypes, $context->getEnabledDoiTypes()));
    }

    /**
     * Add the component state, script and styles to the workflow and author dashboard pages
     *
     * @param string $hookName TemplateManager::display
     * @param array $params [TemplateManager $templateMgr, string $template]
     */
    public function handleTemplateDisplay(string $hookName, array $params): bool
    {
        /** @var TemplateManager $templateMgr */
        $templateMgr = $params[0];
        $template = $params[1];

        if (!in_array($template, ['workflow/workflow.tpl', 'authorDashboard/authorDashboard.tpl'])) {
            return Hook::CONTINUE;
        }

        $request = Application::get()->getRequest();
        $context = $request->getContext();
        if (!$context) {
            return Hook::CONTINUE;
        }

        $enabledTypes = self::getEnabledTypes($context);
        if (empty($enabledTypes)) {
            return Hook::CONTINUE;
        }

        $this->showTab = true;
        $isOmp = Application::get()->getName() === 'omp';

        $templateMgr->setState([self::STATE_KEY => [
            'enabledTypes' => $enabledTypes,
            'i18n' => [
                'type' => __('common.type'),
                'item' => __('plugins.generic.publicationDoi.item'),
                'doi' => __('manager.dois.title'),
                'notAssigned' => __('plugins.generic.publicationDoi.notAssigned'),
                'noItems' => __('plugins.generic.publicationDoi.noItems'),
                'types' => [
                    self::TYPE_PUBLICATION => __('plugins.generic.publicationDoi.type.publication'),
                    self::TYPE_REPRESENTATION => __($isOmp
                        ? 'plugins.generic.publicationDoi.type.publicationFormat'
                        : 'plugins.generic.publicationDoi.type.representation'),
                    self::TYPE_CHAPTER => __('plugins.generic.publicationDoi.type.chapter'),
                    self::TYPE_SUBMISSION_FILE => __('plugins.generic.publicationDoi.type.file'),
                ],
            ],
        ]]);

        // Vue components built with Vite from resources/js (npm run build)
        $buildUrl = $request->getBaseUrl() . '/' . $this->getPluginPath() . '/public/build';
        $templateMgr->addJavaScript(
            'publicationDoi',
            $buildUrl . '/build.iife.js',
            [
                'inline' => false,
                'contexts' => ['backend'],
                'priority' => TemplateManager::STYLE_SEQUENCE_LAST,
            ]
        );
        $templateMgr->addStyleSheet(
            'publicationDoi',
            $buildUrl . '/style.css',
            ['contexts' => ['backend']]
        );

        return Hook::CONTINUE;
    }

    /**
     * Add the tab to the publication tabs
     *
     * @param string $hookName Template::Workflow::Publication
     * @param array $params [array $params, Smarty $smarty, string &$output]
     */
    public function addPublicationTab(string $hookName, array $params): bool
    {
        if (!$this->showTab) {
            return Hook::CONTINUE;
        }

        $smarty = $params[1];
        $output = &$params[2];

        $output .= $smarty->fetch($this->getTemplateResource('publicationDoiTab.tpl'));

        return Hook::CONTINUE;
    }
}
