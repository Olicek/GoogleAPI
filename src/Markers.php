<?php
/**
 * Copyright (c) 2015 Petr Olišar (http://olisar.eu)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Oli\GoogleAPI;

/**
 * Description of Markers
 *
 * @author Petr Olišar <petr.olisar@gmail.com>
 */
class Markers extends \Nette\Object
{

	const DROP = 'DROP', BOUNCE = 'BOUNCE';
	
	/** @var array */
	private $markers = array();

	/** @var String */
	private $iconDefaultPath;

	/** @var Boolean */
	private $bound = FALSE;

	/** @var Boolean */
	private $markerClusterer = FALSE;

	
	/**
	 * @internal
	 * @param array $markers
	 * @throws \Nette\InvalidArgumentException
	 */
	public function addMarkers(array $markers)
	{
		if(count($markers))
		{
			foreach($markers as $marker)
			{
				$this->createMarker($marker);
			}
		}
	}
	
	
	/**
	* @param array $position
	* @param boolean $animation
	* @param String $title
	* @return Markers
	*/
	public function addMarker(array $position, $animation = false, $title = null)
	{
		if (!is_string($animation) && !is_bool($animation))
		{
			throw new \InvalidArgumentException("Animation must be string or boolean, $animation (" .
					gettype($animation) . ") was given");
		}

		$this->markers[] = array(
			'position' => $position,
			'title' => (string)$title,
			'animation' => $animation,
			'visible' => true
		);
		return $this;
	}
	
	
	public function getMarker()
	{
		return end($this->markers);
	}
	
	/**
	 * @return array
	 */
	public function getMarkers()
	{
		return $this->markers;
	}
	
	
	public function deleteMarkers()
	{
		$this->markers = array();
	}
	
	
	/**
	 * @param String $message
	 * @param Boolean $autoOpen
	 * @return Markers
	 */
	public function setMessage($message, $autoOpen = false)
	{
		if (!count($this->markers))
		{
			throw new \InvalidArgumentException("setMessage must be called after addMarker()");
		}
		end($this->markers);         // move the internal pointer to the end of the array
		$key = key($this->markers);
		$this->markers[$key]['message'] = $message;
		$this->markers[$key]['autoOpen'] = $autoOpen;
		return $this;
	}
	
	
	/**
	 *
	 * @param Boolean $cluster
	 * @return \Oli\GoogleAPI\Markers
	 * @throws \InvalidArgumentException
	 */
	public function isMarkerClusterer($cluster = true)
	{
		if (!is_bool($cluster))
		{
			throw new \InvalidArgumentException("cluster must be boolean, $cluster (".gettype($cluster).") was given");
		}
		
		$this->markerClusterer = $cluster;
		return $this;
	}
	
	
	/**
	 *
	 * @return Boolean
	 */
	public function getMarkerClusterer()
	{
		return $this->markerClusterer;
	}
	

	
	/**
	 * @param Boolean $bound Show all of markers
	 * @return \Oli\GoogleAPI\MapAPI
	 */
	public function fitBounds($bound = true)
	{
		if (!is_bool($bound))
		{
			throw new \InvalidArgumentException("fitBounds must be boolean, $bound (".gettype($bound).") was given");
		}

		$this->bound = $bound;
		return $this;
	}
	
	
	/**
	 *
	 * @return Boolean
	 */
	public function getBound()
	{
		return $this->bound;
	}
	
	
	/**
	 *
	 * @param Marker\Icon | String $icon
	 */
	public function setIcon($icon)
	{
		end($this->markers);         // move the internal pointer to the end of the array
		$key = key($this->markers);
		if($icon instanceof Marker\Icon)
		{
			$icon->setUrl(is_null($this->iconDefaultPath) ? $icon->getUrl() : $this->iconDefaultPath . $icon->getUrl());
			$this->markers[$key]['icon'] = $icon->getArray();
			
		} else
		{
			$this->markers[$key]['icon'] = is_null($this->iconDefaultPath) ? $icon : $this->iconDefaultPath . $icon;
		}

		return $this;
	}
	
	
	/**
	 *
	 * @param String $defaultPath
	 * @return \Oli\GoogleAPI\Markers
	 */
	public function setDefaultIconPath($defaultPath)
	{
		if(!is_null($defaultPath) &&
			!\Nette\Utils\Strings::endsWith($defaultPath, '/') &&
			!\Nette\Utils\Strings::endsWith($defaultPath, '\\'))
		{
			$defaultPath .= DIRECTORY_SEPARATOR;
		}
		$this->iconDefaultPath = $defaultPath;
		return $this;
	}


	public function getDefaultIconPath()
	{
		return $this->iconDefaultPath;
	}
	
	
	/**
	 *
	 * @param String $color Color can be 24-bit color or: green, purple, yellow, blue, gray, orange, red
	 * @return \Oli\GoogleAPI\Markers
	 */
	public function setColor($color)
	{
		$allowed = array('green', 'purple', 'yellow', 'blue', 'orange', 'red');
		if (!in_array($color, $allowed))
		{
			if (!\Nette\Utils\Strings::match($color, '~^0x[a-f0-9]{6}$~i'))
			{
				throw new \Nette\InvalidArgumentException('Color must be 24-bit color or from the allowed list.');
			}
		}
		end($this->markers);         // move the internal pointer to the end of the array
		$key = key($this->markers);
		$this->markers[$key]['color'] = $color;
		return $this;
	}


	private function createMarker(array $marker)
	{
		if(!array_key_exists('coordinates', $marker))
		{
			throw new \Nette\InvalidArgumentException('Coordinates must be set in every marker');
		}

		$this->addMarker(array_values($marker['coordinates']),
				isset($marker['animation']) ? $marker['animation'] : false,
				isset($marker['title']) ? $marker['title'] : null);

		if(array_key_exists('message', $marker))
		{
			if(is_array($marker['message']))
			{
				$message = array_values($marker['message']);
				$this->setMessage($message[0], $message[1]);
			} else
			{
				$this->setMessage($marker['message']);
			}
		}

		if(array_key_exists('icon', $marker))
		{
			if(is_array($marker['icon']))
			{
				$icon = new Marker\Icon($marker['icon']['url']);

				if(array_key_exists('size', $marker['icon']))
				{
					$icon->setSize($marker['icon']['size']);
				}

				if(array_key_exists('anchor', $marker['icon']))
				{
					$icon->setAnchor($marker['icon']['anchor']);
				}

				if(array_key_exists('origin', $marker['icon']))
				{
					$icon->setOrigin($marker['icon']['origin']);
				}
				$this->setIcon($icon);

			} else
			{
				$this->setIcon($marker['icon']);
			}
		}

		if(array_key_exists('color', $marker))
		{
			$this->setColor($marker['color']);
		}
	}
}
