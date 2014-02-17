<?php


class Config extends Events3Module
{

    public function Events3PreRun()
    {
        /* @var $events3 Events3 */
        $events3 = Events3::GetHandler();
        $cFile = $events3->ConfigFile;

        // Load the runtime values in a single array
        $aRuntime = (array)@parse_ini_file($cFile);
        $cHashBefore = md5(implode('', array_keys($aRuntime)));

        // Let other modules inject their settings
        $events3->Raise('ConfigInit', $aRuntime);
        $cHashAfter = md5(implode('', array_keys($aRuntime)));

        // If the hashes do not match we need to write
        // the configfile again
        if ($cHashBefore != $cHashAfter) {
            $NewIni = '';
            foreach ($aRuntime as $iniKey => $iniValue) {
                if ($iniKey) {
                    $NewIni .= "{$iniKey}=\"{$iniValue}\"\n";
                }

            }
            file_put_contents($cFile, $NewIni);
        }

    }
}