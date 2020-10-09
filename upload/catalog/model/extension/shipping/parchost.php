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
					'title'        =>  $location['title'],
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
		$data = $this->getClosestParchost($user_coordinates['lat'], $user_coordinates['long']);
		return $data;
	}

	protected function getClosestParchost($lat, $long)
	{
		$cURLConnection = curl_init('https://api.parchost.com/parchost/closest?lat=' . $lat . '&long=' . $long);
		curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
		$apiResponse = curl_exec($cURLConnection);
		curl_close($cURLConnection);

		// $apiResponse - available data from the API request
		$jsonArrayResponse = json_decode($apiResponse, true);

		return $jsonArrayResponse['data'];
	}

	private function getCoordinatesForAddress($address)
	{
		return ["long" => 2.34555, "lat" => 1.23444];
	}
}
