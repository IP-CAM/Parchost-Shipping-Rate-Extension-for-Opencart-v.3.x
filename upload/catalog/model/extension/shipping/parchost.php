<?php

class ModelExtensionShippingParchost extends Model
{
	function getQuote($address)
	{
		$coordinates = $this->getCoordinatesForAddress($address);

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

			$locations = $this->getLocations($address['address_1']);

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

	public function getLocations($address_1)
	{
		$available = [];
		$data = json_decode('{
			"parchost": [
			  {
				"id": 1,
				"title": "Ikeja Shopping Mall - Parchost",
				"cost": "3.01",
				"location": "Ikeja",
				"long":"",
				"lat":""
			  },
			  {
				"id": 2,
				"title": "Allen Avenue - Parchost",
				"cost": "9.14",
				"location": "Ikeja",
				"long":"",
				"lat":""
			  },
			  {
				"id": 3,
				"title": "National Stadium - Parchost",
				"cost": "7.78",
				"location": "Surulere",
				"long":"",
				"lat":""
			  },
			  {
				"id": 4,
				"title": "Magodo Phase 1 - Parchost",
				"cost": "4.81",
				"location": "Magodo",
				"long":"",
				"lat":""
			  },
			  {
				"id": 5,
				"title": "Lekki Tollgate - Parchost",
				"cost": "9.64",
				"location": "Lekki",
				"long":"",
				"lat":""
			  },
			  {
				"id": 6,
				"title": "Lekki Conservation Center - Parchost",
				"cost": "8.51",
				"location": "Lekki",
				"long":"",
				"lat":""
			  }
			]
		  }');

		foreach ($data->parchost as $key => $parchost) {
			if (strpos(strtolower($address_1), strtolower($parchost->location)) !== false) {
				array_push($available, ['id' => $parchost->id, 'location' => $parchost->title, 'cost' => $parchost->cost]);
			}
		}
		return $available;
	}

	private function distance($lat1, $lon1, $lat2, $lon2, $unit)
	{

		$theta = $lon1 - $lon2;
		$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
		$dist = acos($dist);
		$dist = rad2deg($dist);
		$miles = $dist * 60 * 1.1515;
		$unit = strtoupper($unit);

		if ($unit == "K") {
			return ($miles * 1.609344);
		} else if ($unit == "N") {
			return ($miles * 0.8684);
		} else {
			return $miles;
		}
	}

	private function getCoordinatesForAddress($address)
	{
		return ["long" => 2.34555, "lat" => 1.23444];
	}
}
