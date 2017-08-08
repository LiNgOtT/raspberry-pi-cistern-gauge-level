<?php
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Service\WeatherData;
use AppBundle\Service\RaspberryData;
use Symfony\Component\HttpFoundation\Request;

class StatisticController extends Controller
{
    /**
     * @Route("/{_locale}/")
     * @Route("/")
     * @param   Request         $request
     * @param   WeatherData     $weatherData
     * @param   RaspberryData   $raspberryData
     */
    public function showAction(Request $request, WeatherData $weatherData, RaspberryData $raspberryData)
    {
        // process weather data
        $weatherParameter = array(
            'use' => $this->container->getParameter('weather_api.use'),
            'url' => $this->container->getParameter('weather_api.url'),
            'appId' => $this->container->getParameter('weather_api.appId'),
            'city' => $this->container->getParameter('weather_api.city')
        );

        if($weatherParameter['use']) {
            $weatherData->setParameter($weatherParameter);
            $weatherData->requestData();
        }

        // process raspberry pi data
        $chartParameter = array(
            'limit.all' => $this->container->getParameter('chart.limit.all'),
            'limit.day' => $this->container->getParameter('chart.limit.day'),
            'limit.dayConsumption' => $this->container->getParameter('chart.limit.day'),
            'limit.week' => $this->container->getParameter('chart.limit.week'),
            'limit.month' => $this->container->getParameter('chart.limit.month')
        );

        $raspberryData->init($this->get('kernel')->getRootDir(), $chartParameter);
        $raspberryData->read();

        // templating
        return $this->render('gauge/show.html.twig', array(
            'style' => array(
                'title' => array(
                    'color' => '#E0E0E3',
                    'fontSize' => 16
                ),
                'axisTitle' => array(
                    'color' => '#E0E0E3',
                    'fontSize' => 12
                ),
                'text' => array(
                    'color' => '#FFF',
                    'fontSize' => 9
                ),
                'legend' => array(
                    'color' => '#FFF',
                    'fontSize' => 12
                ),
                'animation' => array(
                    'duration' => 4000,
                    'easing' => 'inAndOut',
                    'startup' => 'true'
                )
            ),
            'chartCisternCapacity' => $this->container->getParameter('chart.cistern.capacity'),
            'noAnimation' => !empty($request->query->get('noAnimation')),
            'useWeather' => $weatherParameter['use'],
            'weather' => $weatherData->getData(),
            'raspberry' => $raspberryData->getData()
        ));
    }
}