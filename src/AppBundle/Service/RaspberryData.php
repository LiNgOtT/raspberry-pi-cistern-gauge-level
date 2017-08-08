<?php
namespace AppBundle\Service;
use League\Csv\Reader;
use Symfony\Component\Config\Definition\Exception\Exception;

class RaspberryData
{
    const DATA_FILE = '../var/raspberry/cistern.csv';

    /**
     * @var array
     */
    protected $parameter = array();

    /**
     * @var array
     */
    protected $groupList = array(
        'all', 'day', 'week', 'month', 'dayConsumption'
    );

    /**
     * @var string
     */
    protected $dataFile = '';

    /**
     * @var array
     */
    protected $data = array();

    /**
     * @param $rootDir
     * @param array $parameter
     */
    public function init($rootDir, array $parameter) {

        $this->dataFile = realpath($rootDir . '/' . self::DATA_FILE);
        if(!file_exists($this->dataFile)) {
            touch($this->dataFile);
        }

        $this->parameter = $parameter;
    }

    public function read() {
        // load the CSV document from a file path
        $csv = Reader::createFromPath( $this->dataFile );
        $input_bom = $csv->getInputBOM();
        if ($input_bom === Reader::BOM_UTF16_LE || $input_bom === Reader::BOM_UTF16_BE) {
            $csv->addStreamFilter('convert.iconv.UTF-16/UTF-8');
        }

        $this->processData($csv);
        $this->postProcess();
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $csv
     * @throws \Exception
     */
    protected function processData($csv)
    {
        foreach($csv as $i => $record) {
            $record[0] = date('Y-m-d H:i',strtotime($record[0]));
            $record[1] = intval($record[1]);

            $this->addData('all', $record, function($date) {
                return $date;
            });

            $this->addData('day', $record, function($date) {
                return date('Y-m-d', strtotime($date));
            });

            $this->addData('week', $record, function($date) {
                return sprintf('%s (%d)'
                    , date('F', strtotime($date))
                    , date('W', strtotime($date))
                );
            });

            $this->addData('month', $record, function($date) {
                return sprintf('%s (%d)'
                    , date('F', strtotime($date))
                    , date('Y', strtotime($date))
                );
            });
        }

        if(empty($this->data)) {
            throw new \Exception('No data available. CSV file empty?');
        }
    }

    /**
     * calculates day consumption and reduces the
     * data to given limit
     */
    protected function postProcess() {
        // calculate consumption: day
        $this->data['dayConsumption'] = array( 'label' => array(), 'data' => array());
        foreach($this->data['day']['data'] as $key => $value)
        {
            if($key === 0) {
                continue;
            }

            $this->addData('dayConsumption', array(
                $this->data['day']['label'][$key],
                $value - $this->data['day']['data'][$key-1]
            ), function($date) {
                return date('Y-m-d',strtotime($date));
            });
        }

        // get slice of data
        foreach($this->groupList as $index) {
            $this->data[$index]['label'] = array_slice($this->data[$index]['label'],count($this->data[$index]['label'])-$this->parameter['limit.'.$index]);
            $this->data[$index]['data'] = array_slice($this->data[$index]['data'],count($this->data[$index]['data'])-$this->parameter['limit.'.$index]);
        }
    }

    /**
     * @param $index
     * @param array $record
     * @param $callback
     */
    protected function addData($index, $record, $callback)
    {
        // initiate data structure
        if(!isset($this->data[$index])) {
            $this->data[$index] = array('label' => array(), 'data' => array());
        }

        // fill data with life
        $date = $callback($record[0]);
        if(!in_array($date, $this->data[$index]['label'])) {
            $this->data[$index]['label'][] = $date;
            $this->data[$index]['data'][] = $record[1];
        }
        else
        {
            $this->data[$index]['data'][count($this->data[$index]['data'])-1] = $record[1];
        }
    }
}