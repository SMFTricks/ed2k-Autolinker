<?php

/**
 * @package BBC Topics List
 * @version 1.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2023, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

if (!defined('SMF'))
	die('No direct access...');

class BBC_TopicsList
{
	/**
	 * @var array The BBC's
	 */
	private array $_bbc_list = [
		'tlist',
		'topicslist',
	];

	/**
	 * @var array Numeric characters
	 */
	private array $_numeric_chars = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];

	/**
	 * Initialize the mod
	 */
	public function initialize() : void
	{
		// Load hooks
		$this->hooks();
	}

	/**
	 * Load the hooks for the mod
	 */
	private function hooks() : void
	{
		add_integration_function('integrate_pre_css_output', 'BBC_TopicsList::css#', false);
		add_integration_function('integrate_pre_javascript_output', 'BBC_TopicsList::js#', false);
		add_integration_function('integrate_load_permissions', 'BBC_TopicsList::permissions#', false);
		add_integration_function('integrate_admin_areas', 'BBC_TopicsList::admin_areas#', false);
		add_integration_function('integrate_modify_modifications', 'BBC_TopicsList::modify_modifications#', false);
		add_integration_function('integrate_helpadmin', 'BBC_TopicsList::language#', false);
		add_integration_function('integrate_preparsecode', 'BBC_TopicsList::preparsecode#', false);
		add_integration_function('integrate_bbc_buttons', 'BBC_TopicsList::bbc_buttons#', false);
		add_integration_function('integrate_bbc_codes', 'BBC_TopicsList::bbc_codes#', false);
		add_integration_function('integrate_load_theme', 'BBC_TopicsList::load_theme#', false);
	}

	/**
	 * Language
	 */
	public function language() : void
	{
		loadLanguage('TopicsList/');
	}

	/**
	 * Load the template
	 */
	public function load_theme() : void
	{
		loadTemplate('TopicsList');
	}

	/**
	 * Ádd the permissions
	 * 
	 * @param array $permissionList The list of permissions
	 */
	public function permissions(array &$permissionGroups, array &$permissionList) : void
	{
		$permissionList['membergroup']['TopicsList_use'] = [false, 'general'];
	}

	/**
	 * Add the seciton to the menu
	 * 
	 * @param $areas The admin areas
	 */
	public function admin_areas(array &$areas)
	{
		global $txt;

		$this->language();
		$areas['config']['areas']['modsettings']['subsections']['topicslist'] = [$txt['TopicsList_title']];
	}

	/**
	 * Add the new subaction for the topics list
	 * 
	 * @param $subActions The list of subactions
	 */
	public function modify_modifications(array &$subActions) : void
	{
		$subActions['topicslist'] = __CLASS__ . '::settings#';
	}

	/**
	 * The settings page
	 * 
	 * @param $return_config If the results are being returned to the search page.
	 */
	public function settings(bool $return_config = false)
	{
		global $txt, $context, $scripturl;

		$context['post_url'] = $scripturl . '?action=admin;area=modsettings;sa=topicslist;save';
		$context['page_title'] = $txt['TopicsList_title'];
		$context[$context['admin_menu_name']]['tab_data']['description'] = $txt['TopicsList_description'];

		$config_vars = [
			['int', 'TopicsList_topic_limit', 'subtext' => $txt['TopicsList_topic_limit_desc'], 'min' => 0],
			['check', 'TopicsList_topic_notags', 'subtext' => $txt['TopicsList_topic_notags_desc']],
			['check', 'TopicsList_topic_only', 'subtext' => $txt['TopicsList_topic_only_desc']],
			['permissions', 'TopicsList_use', 'subtext' => $txt['permissionhelp_TopicsList_use']]
		];
		
		// Return config vars
		if ($return_config)
			return $config_vars;

		// Saving?
		if (isset($_GET['save'])) {
			checkSession();
			saveDBSettings($config_vars);
			clean_cache();
			redirectexit('action=admin;area=modsettings;sa=topicslist');
		}
		prepareDBSettingContext($config_vars);
	}

	/**
	 * Add some checks before the message is sent
	 * 
	 * @param string $message The message content
	 */
	public function preparsecode(string &$message) : void
	{
		// Pattern
		$tag_patterns = array();
		foreach ($this->_bbc_list as $bbc)
			$tag_patterns[] = preg_quote($bbc) . '(?:(?!\]).)*?';
		$pattern = '/\[(?:' . implode('|', $tag_patterns) . ')\](.*?)\[\/(?:' . implode('|', $this->_bbc_list) . ')\]/';

		// If the user is not an admin, can't use any of the tags.
		if (!allowedTo('TopicsList_use'))
			$message = preg_replace($pattern, '$1', $message);
	}

	/**
	 * Add the bbc to the editor toolbar
	 * 
	 * @param array $tags The bbc tags
	 * @return void
	 */
	public function bbc_buttons(array &$bbc_tags) : void
	{
		global $txt, $editortxt;

		// Permission to use these?1
		if (!allowedTo('TopicsList_use'))
			return;

		$this->language();
		addJavaScriptVar('bbc_topicslist_title', $txt['TopicsList_insert_title'], true);
		addJavaScriptVar('bbc_topicslist_default', $txt['TopicsList_default_text'], true);
		addJavaScriptVar('bbc_topicslist_board', $txt['TopicsList_insert_board'], true);
		addJavaScriptVar('bbc_topicslist_board_desc', $txt['TopicsList_insert_board_desc'], true);
		addJavaScriptVar('bbc_topicslist_insert', $editortxt['insert'], true);
		addJavaScriptVar('bbc_topicslist_alphanumeric', $txt['TopicsList_insert_alphanumeric'], true);
		addJavaScriptVar('bbc_topicslist_include', $txt['TopicsList_insert_include'], true);
		addJavaScriptVar('bbc_topicslist_include_desc', $txt['TopicsList_insert_include_desc'], true);
		addJavaScriptVar('bbc_topicslist_include_placeholder', $txt['TopicsList_insert_include_placeholder'], true);

		// Add the BBCs
		$bbc_tags[][] = [
			'image' => 'topicslist',
			'code' => 'topicslist',
			'description' => $txt['TopicsList_insert_desc']
		];
	}

	/**
	 * Attach the content to the bbc.
	 * 
	 * @param array $codes The bbc codes
	 * @param array $no_autolink_tags Disable autolink for these tags
	 * @return void
	 */
	public function bbc_codes(array &$codes, array &$no_autolink_tags) : void
	{
		global $txt;

		$this->language();

		// Add the BBCs
		foreach ($this->_bbc_list as $bbc)
		{
			// Don't autolink this bbc
			$no_autolink_tags[] = $bbc;

			// Add the bbc
			$codes[] = [
				'tag' => $bbc,
				'type' => 'unparsed_content',
				'parameters' => [
					'board' => [
						'optional' => true,
						'quote' => true,
						'default' => 0,
						'match' => '(\d+)',
					],
					'include' => [
						'optional' => true,
						'quote' => true,
					],
					'alphanumeric' => [
						'optional' => true,
						'quote' => true,
						'match' => '(true|false)'
					],
				],
				'content' => '<div class="roundframe bbc_topicslist">$1</div>',
				'disabled_content' => '<div class="noticebox">' . $txt['TopcisList_disabled'] . '</div>',
				'validate' => isset($disabled['code']) ? null : function(&$tag, &$data, $disabled, $params)
				{
					// Handle the bbc somewhere else.
					$this->getList($data, $params);
				},
				'block_level' => true,
				'disallow_children' => true,
			];
		}
	}

	/**
	 * Get the topics list
	 * 
	 * @param string $data The content of the BBC
	 * @param array $params The bbc parameters
	 */
	private function getList(string &$data, array $params) : void
	{
		global $smcFunc, $txt, $scripturl, $modSettings, $context, $board, $topic, $user_info;

		// List title
		$context['list_topics_title'] = $data;

		// Default data
		$data = $txt['TopicsList_no_data'];

		// Only in topics?
		if (!empty($modSettings['TopicsList_topic_only']) && empty($board) && empty($topic))
			return;

		// Topcis list for the template
		$context['list_topics'] = [];
		$context['list_topics_index'] = [];
	
		if (($context['list_topics'] = cache_get_data('bbc_topicslist_b' . (int) $params['{board}'] . '_u' . $user_info['id'] . (!empty($params['{alphanumeric}']) && $params['{alphanumeric}'] === 'true' ? '_alphanum' : '') . (!empty($params['{include}']) ? '_inc-' . $params['{include}'] : ''), $context['list_topics'], 3600)) === null || ($context['list_topics_index'] = cache_get_data('bbc_topicslistindex_b' . (int) $params['{board}'] . '_u' . $user_info['id'] . (!empty($params['{alphanumeric}']) && $params['{alphanumeric}'] === 'true' ? '_alphanum' : '') . (!empty($params['{include}']) ? '_inc-' . $params['{include}'] : ''), $context['list_topics_index'], 3600)) === null)
		{
			$result = $smcFunc['db_query']('', '
				SELECT
					t.id_topic, t.id_board, t.approved, t.id_first_msg, TRIM(m.subject) as subject
				FROM {db_prefix}topics as t
				JOIN {db_prefix}messages as m ON (m.id_msg = t.id_first_msg)
				WHERE t.approved = {int:approved}
					AND {query_see_topic_board}' . (empty($params['{board}']) ? '' : '
					AND t.id_board = {int:board}') . '
				ORDER BY subject' . (empty($modSettings['TopicsList_topic_limit']) ? '' : '
				LIMIT {int:limit}'),
				[
					'board' => (int) $params['{board}'],
					'limit' => (int) $modSettings['TopicsList_topic_limit'],
					'approved' => 1,
				]
			);

			$context['list_topics'] = [
				'0-9' => [],
			];
			$context['list_topics_index'] = ['0-9'];
			$context['list_included_chars'] = !empty($params['{include}']) ? explode(',', $params['{include}']) : [];

			// Make the characters uppercase
			if (!empty($context['list_included_chars']))
			{
				foreach ($context['list_included_chars'] as $key => $included_character)
				{
					$context['list_included_chars'][$key] = mb_strtoupper($included_character);
				}
			}

			while ($row = $smcFunc['db_fetch_assoc']($result))
			{
				// Remove initial tags?
				if (!empty($modSettings['TopicsList_topic_notags']))
					$row['subject'] = preg_replace('/^\[[^\]]+\]\s*/', '', $row['subject']);

				// Initial letter
				$initial_character = mb_substr(mb_strtoupper($row['subject']), 0, 1);

				// Included?
				if (!empty($context['list_included_chars']) && !in_array($initial_character, $context['list_included_chars']))
					continue;

				// Regex?
				if (!empty($params['{alphanumeric}']) && $params['{alphanumeric}'] === 'true' && preg_match('/^[^\p{L}\p{N}]/u', $initial_character))
					continue;

				$context['list_topics_index'][] = $initial_character;

				// Add the topic
				$context['list_topics'][$initial_character][$row['id_topic']] = $row;
			}
			$smcFunc['db_free_result']($result);

			// Topics?
			if (empty($context['list_topics']))
				return;

			// Group the numbers
			foreach ($context['list_topics'] as $initial_character => $character_data)
			{
				if (!in_array($initial_character, $this->_numeric_chars))
					continue;

				$context['list_topics']['0-9'] += $character_data;
				unset($context['list_topics'][$initial_character]);
			}

			// Dump the numeric index
			if (empty($context['list_topics']['0-9']))
			{
				unset($context['list_topics']['0-9']);
				unset($context['list_topics_index'][0]);
			}

			$context['list_topics_index'] = array_unique($context['list_topics_index']);

			// Cache!
			cache_put_data('bbc_topicslist_b' . (int) $params['{board}'] . '_u' . $user_info['id'] . (!empty($params['{alphanumeric}']) && $params['{alphanumeric}'] === 'true' ? '_alphanum' : '') . (!empty($params['{include}']) ? '_inc-' . $params['{include}'] : ''), $context['list_topics'], 3600);
			cache_put_data('bbc_topicslistindex_b' . (int) $params['{board}'] . '_u' . $user_info['id'] . (!empty($params['{alphanumeric}']) && $params['{alphanumeric}'] === 'true' ? '_alphanum' : '') . (!empty($params['{include}']) ? '_inc-' . $params['{include}'] : ''), $context['list_topics_index'], 3600);
		}
		
		$data = template_topics_list();

		// There's a board?
		if (!empty($params['{board}']))
		{
			$data .= '<br><a class="button" href="' . $scripturl . '?board=' . $params['{board}'] . '.0">' . $txt['all'] . '</a>';
		}
	}

	/**
	 * Load the CSS
	 */
	public function css() : void
	{
		loadCSSFile('bbc_topicslist.css', ['minimize' => true, 'default_theme' => true], 'smf_bbc_topicslist');
	}

	/**
	 * Load the JS for the topics list
	 */
	public function js() : void
	{
		loadJavaScriptFile('bbc_topicslist.js', ['minimize' => true, 'default_theme' => true, 'defer' => true], 'smf_bbc_topicslist');
	}
}