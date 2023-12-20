<?php

/**
 * @package Ed2k Autolinker
 * @version 1.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2023, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

if (!defined('SMF'))
	die('No direct access...');

class Ed2k
{
	/**
	 * @var string Pattern
	 */
	private string $_pattern = '~(\s|<br>|\n|^)(?!.*?\[ed2k\].*?\[/ed2k\])ed2k://\|file\|(.+?)\|(.+?)\|.+?(\s|<br>|\n|$)~i';

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
		add_integration_function('integrate_preparsecode', 'Ed2k::preparsecode#', false);
		add_integration_function('integrate_bbc_codes', 'Ed2k::bbc_codes#', false);
	}

	/**
	 * Add some checks before the message is sent
	 * 
	 * @param string $message The message content
	 */
	public function preparsecode(string &$message) : void
	{
		$message = preg_replace_callback($this->_pattern, [$this, 'ed2k_to_bbc'], $message);
	}

	/**
	 * Set the actual format for the BBC
	 * 
	 * @param array The matching strings
	 */
	private function ed2k_to_bbc(array $matches) : string
	{
		$ed2k_link = str_replace('<br>', '', trim($matches[0]));
		return '<br>[ed2k]' . $ed2k_link . '[/ed2k]<br>';
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
		global $settings;

		// Don't autolink this bbc
		$no_autolink_tags[] = 'ed2k';

		// Add the bbc
		$codes[] = [
			'tag' => 'ed2k',
			'type' => 'unparsed_content',
			'parameters' => [
				'title' => [
					'optional' => true,
					'quote' => true,
				],
				'noid' => [
					'optional' => true,
					'quote' => true,
					'match' => '(true)',
				]
			],
			'content' => '<div style="padding: 0.5em 1.5em; display: flex; gap: 0.5em; align-items: center; flex-wrap: wrap;">$1</div>',
			'validate' => isset($disabled['code']) ? null : function(array &$tag, string &$data, array $disabled, array $params) use ($settings)
			{
				// Get the information/details for this URL
				$ed2k_data = explode('|', $data);
				$title = $ed2k_data[2] ?? false;
				$id = $ed2k_data[4] ?? false;

				// Set the download link
				$data = '
					<img src="' . $settings['default_images_url'] . '/ed2k.png">
					<a href="' . $data . '">
						' . ($params['{title}'] ?: $title ?? $data) . '
					</a>';

				if (!empty($id) && $params['{noid}'] !== 'true')
				{
					$data .= '<a rel="noopener" target="_blank" href="http://ed2k.shortypower.dyndns.org/?hash=' . $id . '"><span class="main_icons stats"></span></a>';
				}
			},
			'block_level' => true,
			'disallow_children' => true,
			];
	}
}