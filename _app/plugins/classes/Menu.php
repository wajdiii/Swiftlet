<?php
/**
 * @package Swiftlet
 * @copyright 2009 ElbertF http://elbertf.com
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU Public License
 */

if ( !isset($app) ) die('Direct access to this file is not allowed');

/**
 * Menu
 * @abstract
 */
class Menu
{
	public
		$ready
		;

	private
		$app,
		$view,
		$controller
		;

	/**
	 * Initialize
	 * @param object $app
	 */
	function __construct($app)
	{
		$this->app        = $app;
		$this->view       = $app->view;
		$this->controller = $app->controller;

		if ( !empty($app->db->ready) )
		{
			/**
			 * Check if the menu table exists
			 */
			if ( in_array($app->db->prefix . 'menu', $app->db->tables) )
			{
				$this->ready = TRUE;
			}
		}
	}

	/* Get menu items
	 * @param array $params
	 */
	function get_items(&$params)
	{
		$this->app->db->sql('
			SELECT
				`items`
			FROM `' . $this->app->db->prefix . 'menu`
			LIMIT 1
			;');

		if ( $r = $this->app->db->result )
		{
			$items = @unserialize($r[0]['items']);

			if ( is_array($items) )
			{
				$nodeIds = array();
				$nodes   = array();

				foreach ( $items as $item )
				{
					if ( $item['node_id'] )
					{
						$nodeIds[] = ( int ) $item['node_id'];
					}
				}

				if ( $nodeIds )
				{
					$this->app->db->sql('
						SELECT
							`id`,
							`title`,
							`path`
						FROM `' . $this->app->db->prefix . 'nodes`
						WHERE
							`id` IN (' . implode(', ', $nodeIds) . ')
						LIMIT ' . count($nodeIds) .'
						;');

					if ( $r = $this->app->db->result )
					{
						foreach ( $r as $d )
						{
							$nodes[$d['id']] = array(
								'title' => $d['title'],
								'path'  => $d['path'] ? $d['path'] : 'node/' . $d['id']
								);
						}
					}
				}

				foreach ( $items as $item )
				{
					if ( ( in_array($item['node_id'], $nodeIds) && isset($nodes[$item['node_id']]) ) || !in_array($item['node_id'], $nodeIds) )
					{
						$path  = $item['path']  ? $item['path']  : ( !empty($nodes[$item['node_id']]['path'])  ? $nodes[$item['node_id']]['path']  : '' );
						$title = $item['title'] ? $item['title'] : ( !empty($nodes[$item['node_id']]['title']) ? $nodes[$item['node_id']]['title'] : $item['path'] );

						$params[$title] = $path;
					}
				}
			}
		}
	}
}