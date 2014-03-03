<?php

class IdfixDebug extends Events3Module
{

    private $fStart = null;

    /**
     * Only set start time
     * 
     * @return void
     */
    public function __construct()
    {
        $this->fStart = (float)microtime(true);
    }

    /**
     * Configuration settings that can be overruled
     * in the configurationfile
     * 
     * @param array &$aConfig Reference to the configuration array
     * @return void
     */
    public function Events3ConfigInit(&$aConfig)
    {
        $cKey = 'IdfixDebugShowDebugInfo';
        $aConfig[$cKey] = isset($aConfig[$cKey]) ? $aConfig[$cKey] : '0';
        $this->$cKey = $aConfig[$cKey];

        $cKey = 'IdfixDebugShowProfiler';
        $aConfig[$cKey] = isset($aConfig[$cKey]) ? $aConfig[$cKey] : '0';
        $this->$cKey = $aConfig[$cKey];

    }

    /**
     * $this->Debug()
     *
     * @param $message
     *   The text to show in the display
     * @param $vars
     *   The parameters to show in the second column
     * @param $type
     *   Type of message as defined above this function
     * @return
     *   Full HTML with the complete table of messages
     */
    function Debug($message = null, $vars = null, $type = 1)
    {
        if (!$this->IdfixDebugShowDebugInfo)
        {
            return;
        }

        $this->Profiler(__method__, 'start');

        if (is_null($message))
        {
            $list = (array )@$_SESSION['idfix']['debugtrail'];
            unset($_SESSION['idfix']['debugtrail']);
            return $list;
        }


        if (!isset($_SESSION['idfix']['debugtrail']))
        {
            $_SESSION['idfix']['debugtrail'] = array();
        }

        if (!is_NULL($message))
        {
            $messages = array(1 => 'Debug');
            $_SESSION['idfix']['debugtrail'][] = array(
                round(((float)microtime(true) - $this->fStart) * 1000, 2),
                $messages[$type],
                $message,
                print_r($vars, true));
        }
        $this->Profiler(__method__, 'stop');
        return $_SESSION['idfix']['debugtrail'];
    }

    /**
     * idfix_footer()
     *
     * Implements hook_footer().
     *
     * @return
     *   Generated HTML
     */
    function Events3PostRun()
    {
        if (!$this->IdfixDebugShowProfiler and !$this->IdfixDebugShowDebugInfo)
        {
            return '';
        }

        $this->Debug(__method__, null);

        $header = array(
            'timer',
            'type',
            'message',
            'parameters',
            );
        $info = $this->Debug(null, null);
        $retval = '<br /><hr>';
        if (is_array($info) and count($info) > 0)
        {
            if ($this->IdfixDebugShowDebugInfo)
            {
                $retval .= $this->ThemeTable($header, $info);
            }
        }
        if ($this->IdfixDebugShowProfiler)
        {
            $retval .= $this->Profiler('', 'render');
        }

        $retval .= '<br /><hr>';

        echo $retval;
        //return $retval;
    }

    /**
     * $this->Profiler()
     *
     * @param
     *   string, name of the item in the profiler list
     * @param
     *   string,'start' or 'stop'
     * @return
     *   NULL
     */
    function Profiler($name = '', $op = 'start')
    {
        if (!$this->IdfixDebugShowProfiler)
        {
            return '';
        }

        static $timers = array();
        if ($op == 'start')
        {
            $timers[$name]['start'] = (float)microtime(true);
            if (!isset($timers[$name]['count']))
            {
                $timers[$name]['count'] = 0;
            }
            if (!isset($timers[$name]['total']))
            {
                $timers[$name]['total'] = 0;
            }
            return;
        } elseif ($op == 'stop')
        {
            $timers[$name]['count']++;
            $diff = (float)microtime(true) - $timers[$name]['start'];
            $timers[$name]['total'] += (float)$diff;
            return;
        } elseif ($op == 'render')
        {
            $header = array(
                'name',
                'count',
                'time',
                );
            $data = array();
            foreach ($timers as $timer_name => $timer_data)
            {
                $row = array(
                    $timer_name,
                    $timer_data['count'],
                    round($timer_data['total'] * 1000, 1));
                $data[] = $row;
            }
            return $this->ThemeTable($header, $data);

        }

        return null;
    }

    private function ThemeTable($aHead, $aBody)
    {
        //print_r(get_defined_vars());
        return $this->Idfix->RenderTemplate('ActionListMain', get_defined_vars());
    }


}
