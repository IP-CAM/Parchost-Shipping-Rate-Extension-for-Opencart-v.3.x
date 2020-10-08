<?php

class ModelExtensionShippingParchost extends Model
{
	function getQuote($address)
	{
		$user_coordinates = $this->getCoordinatesForAddress($address);

		$this->load->language('extension/shipping/parchost');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('shipping_parchost_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		if (!$this->config->get('shipping_parchost_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = array();

		if ($status) {
			$quote_data = array();

			$this->load->model('localisation/location');

			$locations = $this->getLocations($user_coordinates);

			foreach ($locations as $location) {

				$quote_data['parchost_' . $location['id']] = array(
					'code'         => 'parchost.parchost_' . $location['id'],
					'title'        =>  $location['location'],
					'cost'         => $location['cost'],
					'tax_class_id' => 0,
					'text'         => $this->currency->format($location['cost'], $this->session->data['currency'])
				);
			}

			$method_data = array(
				'code'       => 'parchost',
				'title'      => $this->language->get('text_title'),
				'quote'      => $quote_data,
				'sort_order' => $this->config->get('shipping_parchost_sort_order'),
				'error'      => false
			);
		}

		return $method_data;
	}

	public function getLocations($user_coordinates)
	{
		$available = [];
		$data = json_decode('{
			"parchosts": [
			  {
				"id": 1,
				"title": "Ikeja Shopping Mall - Parchost",
				"cost": "3.01",
				"location": "Ikeja",
				"long":"1.23444",
				"lat":"2.34555"
			  },
			  {
				"id": 2,
				"title": "Allen Avenue - Parchost",
				"cost": "9.14",
				"location": "Ikeja",
				"long":"1.56444",
				"lat":"2.345456"
			  },
			  {
				"id": 3,
				"title": "National Stadium - Parchost",
				"cost": "7.78",
				"location": "Surulere",
				"long":"1.673444",
				"lat":"2.346345"
			  },
			  {
				"id": 4,
				"title": "Magodo Phase 1 - Parchost",
				"cost": "4.81",
				"location": "Magodo",
				"long":"1.83444",
				"lat":"2.83243"
			  },
			  {
				"id": 5,
				"title": "Lekki Tollgate - Parchost",
				"cost": "9.64",
				"location": "Lekki",
				"long":"1.90444",
				"lat":"2.45946"
			  },
			  {
				"id": 6,
				"title": "Lekki Conservation Center - Parchost",
				"cost": "8.51",
				"location": "Lekki",
				"long":"1.9999",
				"lat":"2.4564565"
			  }
			]
		  }');

		// Find Closesth Parchost
		$closestParchosts = $this->findClosestParchost($data->parchosts, $user_coordinates);

		// fetch parchost
		foreach ($data->parchosts as $parchost) {
			if (isset($closestParchosts[$parchost->id])) {
				array_push($available, ['id' => $parchost->id, 'location' => $parchost->title, 'cost' => $parchost->cost]);
			}
		}
		return $available;
	}

	private function findClosestParchost($parchosts, $user_coordinates)
	{
		$closest = [];
		foreach ($parchosts as $key => $parchost) {
			$disatance = $this->getDistance($user_coordinates['lat'], $user_coordinates['long'], $parchost->lat, $parchost->long);
			$closest[$parchost->id] = $disatance;
		}
		asort($closest);
		return array_slice($closest, 0, 3, true);
	}


	/*::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::*/
	/*::                                                                         :*/
	/*::  This routine calculates the distance between two points (given the     :*/
	/*::  latitude/longitude of those points). It is being used to calculate     :*/
	/*::  the distance between two locations using GeoDataSource(TM) Products    :*/
	/*::                                                                         :*/
	/*::  Definitions:                                                           :*/
	/*::    South latitudes are negative, east longitudes are positive           :*/
	/*::                                                                         :*/
	/*::  Passed to function:                                                    :*/
	/*::    lat1, lon1 = Latitude and Longitude of point 1 (in decimal degrees)  :*/
	/*::    lat2, lon2 = Latitude and Longitude of point 2 (in decimal degrees)  :*/
	/*::                                                                         :*/
	/*::  Worldwide cities and other features databases with latitude longitude  :*/
	/*::  are available at https://www.geodatasource.com                          :*/
	/*::                                                                         :*/
	/*::  For enquiries, please contact sales@geodatasource.com                  :*/
	/*::                                                                         :*/
	/*::  Official Web site: https://www.geodatasource.com                        :*/
	/*::                                                                         :*/
	/*::         GeoDataSource.com (C) All Rights Reserved 2018                  :*/
	/*::                                                                         :*/
	/*::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::*/
	protected function getDistance($lat1, $lon1, $lat2, $lon2)
	{
		if (($lat1 == $lat2) && ($lon1 == $lon2)) {
			return 0;
		} else {
			$theta = $lon1 - $lon2;
			$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
			$dist = acos($dist);
			$dist = rad2deg($dist);
			$miles = $dist * 60 * 1.1515;
			// return in kilo meters
			return $miles * 1.609344;
		}
	}

	private function getCoordinatesForAddress($address)
	{
		return ["long" => 2.34555, "lat" => 1.23444];
	}
}
