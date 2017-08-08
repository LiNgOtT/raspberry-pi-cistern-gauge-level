<?php
namespace AppBundle\Service;
use Curl;

class WeatherData
{
    /**
     * @var array
     */
    protected $apiParameter = array();

    /**
     * @var array
     */
    protected $data = array();

    /**
     * @param array $paramter
     */
    public function setParameter($parameter) {
        $this->apiParameter = $parameter;
    }

    /**
     * @return array|mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * requests the API
     * @throws \Exception
     */
    public function requestData() {

        if(empty($this->apiParameter['url']) || empty($this->apiParameter['appId']) || empty($this->apiParameter['city'])) {
            throw new \Exception(sprintf( 'Weather credentials not set %s' ,var_export($this->apiParameter,true)));
        }

        $curl = new Curl\Curl();
        $weatherData = $curl->get($this->apiParameter['url'], array(
            'q' => $this->apiParameter['city'],
            'mode' => 'json',
            'appid' => $this->apiParameter['appId'],
            'units' => 'metric',
            'cnt' => 25
        ));
        $curl->close();
        $dataRaw = json_decode($weatherData->response);

        if($dataRaw->cod == 401) {
            throw new \Exception($dataRaw->message);
        }

        // process weather data
        $data = array('hour' => array(), 'temp' => array(), 'rain' => array());
        foreach($dataRaw->list as $value) {
            $data['hour'][] = date('H:i', $value->dt);
            $data['temp'][] = intval($value->main->temp);

            if(isset($value->rain)) {
                $rain = get_object_vars($value->rain);
                $data['rain'][] = empty($rain['3h']) ? 0 : round($rain['3h'], 3);
            }
            else
            {
                $data['rain'][] = 0;
            }
        }

        $this->data = $data;
    }
}