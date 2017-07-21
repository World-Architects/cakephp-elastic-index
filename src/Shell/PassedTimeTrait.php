<?php
namespace Psa\ElasticIndex\Shell;

trait PassedTimeTrait {

    /**
     * Start time of a process
     *
     * @var \DateTime|null
     */
    protected $_startTime = null;

    /**
     * Sets the start time
     *
     * @return void
     */
    public function startTimer()
    {
        $this->_startTime = time();
    }

    /**
     * Calculates the times
     *
     * @param int $duration
     * @return array
     */
    public function calculatePassedTime($duration)
    {
        return [
            'days' => floor($duration / (3600 * 24)),
            'hours' => floor(($duration / 3600) % 24),
            'minutes' => floor(($duration / 60) % 60),
            'seconds' => ($duration % 60)
        ];
    }

    /**
     * Prints the passed time since _startStartTime()
     *
     * @return void
     */
    public function showPassedTime()
    {
        $time = $this->calculatePassedTime(time() - $this->_startTime);

        $output = [
            __n('{0} Day', '{0} Days', $time['days'], [$time['days']]),
            __n('{0} Hour', '{0} Hours', $time['hours'], [$time['hours']]),
            __n('{0} Minute', '{0} Minutes', $time['minutes'], [$time['minutes']]),
            __n('{0} Second', '{0} Seconds', $time['seconds'], [$time['seconds']])
        ];

        foreach ($time as $key => $value) {
            if ($value === 0) {
                unset($output[$key]);
            }
            break;
        }

        return implode(', ', $output);
    }
}
