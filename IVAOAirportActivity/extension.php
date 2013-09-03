<?php
// IVAO Airport Activity Extension for Bolt, by François Versmissen

namespace IVAOAirportActivity;

class Extension extends \Bolt\BaseExtension
{

    /**
     * Info block for IVAO Airport Activity Extension.
     */
    function info()
    {

        $data = array(
            'name' => "IVAO Airport Activity",
            'description' => "Get the activity for a specific airport on the IVAO network",
            'keywords' => "IVAO, Airport, Activity",
            'author' => "François Versmissen",
            'link' => "https://github.com/frans2526/ivaoAirportActivity",
            'version' => "0.1",
            'required_bolt_version' => "1.0.2",
            'highest_bolt_version' => "1.0.2",
            'type' => "General",
            'first_releasedate' => "2013-08-18",
            'latest_releasedate' => "2013-08-18",
            'dependencies' => "",
            'priority' => 10
        );

        return $data;

    }

    /**
     * Initialize IVAO Airport Activity. Called during bootstrap phase.
     */
    function init()
    {

        // If yourextension has a 'config.yml', it is automatically loaded.
        // $foo = $this->config['bar'];

        // Initialize the Twig function
        $this->addTwigFunction('iaa', 'twigIAA');

    }

    /**
     * Twig function {{ iaa() }} in IVAO Airport Activity extension.
     */
    function twigIAA($ICAO_code="")
    {
        $html = '';

        $lien_pilots = 'http://webeye.ivao.aero/get.php?t=pilots';
        $lien_atc = 'http://webeye.ivao.aero/get.php?t=atc';


        //Pilots
        if( !file_exists('cache_pilots.txt') || (time()-filemtime('cache_pilots.txt') > 240) ){
            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, $lien_pilots);
            curl_setopt($curl, CURLOPT_COOKIESESSION, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            $return_pilots = curl_exec($curl);
            curl_close($curl);

            unset($curl);

            $fp = fopen('cache_pilots.txt', 'w');
            fwrite($fp, $return_pilots);
            fclose($fp);
            unset($fp);
            chmod('cache_pilots.txt', 0755);
            
        }else{
            $fp = fopen('cache_pilots.txt', 'r');
            $return_pilots = fread($fp, filesize('cache_pilots.txt'));
            fclose($fp);
        }


        //ATC
        if( !file_exists('cache_atc.txt') || (time()-filemtime('cache_atc.txt') > 240) ){

            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, $lien_atc);
            curl_setopt($curl, CURLOPT_COOKIESESSION, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $return_atc = curl_exec($curl);

            curl_close($curl);

            unset($curl);

            $fp = fopen('cache_atc.txt', 'w');
            fwrite($fp, $return_atc);
            fclose($fp);
            unset($fp);
            chmod('cache_atc.txt', 0755);

        }else{
            $fp = fopen('cache_atc.txt', 'r');
            $return_atc = fread($fp, filesize('cache_atc.txt'));
            fclose($fp);
        }

        $return_pilots = json_decode($return_pilots);
        $return_atc = json_decode($return_atc);


        $html .= '<p>Take-off</p><ul>';
        $activity_pilots_dep = false;
        //Pilots Dep
        foreach ($return_pilots as $k => $v) {
            if($v->dep_icao == $ICAO_code){
                $html .= '<li>'.$v->callsign.' ('.$v->dep_icao.'->'.$v->dest_icao.')</li>';
                $activity_pilots_dep = true;
            }
        }
        if(!$activity_pilots_dep){
            $html .= '<li>No take-off</li>';
        }

        $activity_pilots_arr = false;
        $html .= '</ul><p>Landing</p><ul>';
        //Pilots Arr
        foreach ($return_pilots as $k => $v) {
            if($v->dest_icao == $ICAO_code){
                $html .= '<li>'.$v->callsign.' ('.$v->dep_icao.'->'.$v->dest_icao.')</li>';
                $activity_pilots_arr = true;
            }
        }
        if(!$activity_pilots_arr){
            $html .= '<li>No landing</li>';
        }

        $activity_atc = false;
        $html .= '</ul><p>Control</p><ul>';
        //ATC
        foreach ($return_atc as $k => $v) {
            if( strpos($v->callsign, $ICAO_code) === 0){
                $html .= '<li>'.$v->callsign.' ('.$v->freq.'Mhz)</li>';
                $activity_atc = true;
            }
        }
        if(!$activity_atc){
            $html .= '<li>No control</li>';
        }
        $html .= '</ul>';


        return new \Twig_Markup($html, 'UTF-8');

    }


}


