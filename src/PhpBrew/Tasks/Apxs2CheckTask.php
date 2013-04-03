<?php
namespace PhpBrew\Tasks;
use Exception;
use PhpBrew\Utils;

class Apxs2CheckTask extends BaseTask
{


    public function check($build)
    {
        $apxs = $build->getVariant('apxs2');

        if( ! $apxs ) {
            $apxs = Utils::findbin('apxs');
        }

        $this->logger->debug("Found apxs2 sbin: $apxs");

        // use apxs to check module dir permission
        if( $apxs && $libdir = trim( Utils::pipe_execute( "$apxs -q LIBEXECDIR" ) ) ) {
            if( false === is_writable($libdir) ) {
                $msg = array();
                throw new Exception("Apache module dir $libdir is not writable.\nPlease consider using chmod or sudo.");
            }
        }
        if( $apxs && $confdir = trim( Utils::pipe_execute( "$apxs -q SYSCONFDIR" ) ) ) {
            if( false === is_writable($confdir) ) {
                $msg = array();
                $msg[] = "Apache conf dir $confdir is not writable for phpbrew.";
                $msg[] = "Please consider using chmod or sudo: ";
                $msg[] = "    \$ sudo chmod -R og+rw $confdir";
                throw new Exception( join("\n", $msg ) );
            }
        }
    }


}



