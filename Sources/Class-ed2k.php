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
	private string $_pattern = '/(?<!\")(ed2k:\/\/[\s\S]*?\|\/)/';

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
		add_integration_function('integrate_post_parsebbc', 'Ed2k::postparsecode#', false);
	}

	/**
	 * Add some checks after the message is parsed
	 * 
	 * @param string $message The message content
	 * @return string The replacement string
	 */
	public function postparsecode(string &$message) : void
	{
		$message = preg_replace_callback($this->_pattern, [$this, 'ed2k_to_link'], $message);
	}

	/**
	 * Format the ed2k link
	 * 
	 * @param array The matched part of the message
	 */
	private function ed2k_to_link(array $matches) : string
	{
		global $settings;

		$ed2k_link = $matches[0];

		// Get the information/details for this URL
		$ed2k_data = explode('|', $ed2k_link);
		$title = $ed2k_data[2] ?? $ed2k_link;
		$size = $ed2k_data[3] ?? 0;
		$id = $ed2k_data[4] ?? false;

		// Set the download link
		$ed2k_link = '
			<img src="' . $settings['default_images_url'] . '/ed2k.gif">
			<a href="' . $ed2k_link . '">
				' . $title . '
			</a>';

		// File size
		if (!empty($size))
		{
			$ed2k_link .= '<strong>(' . $this->fileSize($size) . ')</strong>';
		}

		// ID with link
		if (!empty($id))
		{
			$ed2k_link .= '<a style="margin-inline-start: 1em;" rel="noopener" target="_blank" href="http://ed2k.shortypower.dyndns.org/?hash=' . $id . '"><span class="main_icons stats"></span></a>';
		}

		return $ed2k_link;
	}

	/**
	 * Get the filesize
	 * 
	 * @param int The size of the file
	 * @return string The formatted filesize
	 */
	private function fileSize(int $size) : string
	{
		$units = ['B', 'KB', 'MB', 'GB'];

		$i = 0;
		while ($size >= 1024 && $i < count($units) - 1)
		{
			$size /= 1024;
			$i++;
		}
		return round($size, 2) . ' ' . $units[$i];
	}
}