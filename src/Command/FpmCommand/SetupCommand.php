<?php

namespace PHPBrew\Command\FpmCommand;

use CLIFramework\Command;
use Exception;
use PHPBrew\Config;

class SetupCommand extends Command
{
    public function brief()
    {
        return 'Generate and setup FPM startup config';
    }

    public function usage()
    {
        return 'phpbrew fpm setup [--systemctl|--initd|--launchctl] [--stdout] [<build name>]';
    }

    public function options($opts)
    {
        $opts->add(
            'systemctl',
            "Generate systemd service entry. " .
            "This option only works for systemd-based Linux. " .
            "To use this option, be sure to compile PHP with --with-fpm-systemd option. " .
            "Start from 1.22, phpbrew automatically add --with-fpm-systemd when systemd is detected."
        );
        $opts->add(
            'initd',
            'Generate init.d script. ' .
            'The generated init.d script depends on lsb-base >= 4.0. ' .
            'If initctl is based on upstart, the init.d script will not be executed. ' .
            'To check, please run /sbin/initctl --version in the command-line.'
        );
        $opts->add('launchctl', 'Generate plist for launchctl (OS X)');
        $opts->add('stdout', 'Print config to STDOUT instead of writing to the file.');
    }

    public function arguments($args)
    {
        $args->add('buildName')->optional();
    }

    public function execute($buildName = null)
    {
        if (!$buildName) {
            $buildName = Config::getCurrentPhpName();
        }
        if (!$buildName) {
            throw new Exception("PHPBREW_PHP is not set. You should provide the build name in the command.");
        }

        fprintf(
            STDERR,
            "*WARNING* php-fpm --pid option requires php >= 5.6. "
            . "You need to update your php-fpm.conf for the pid file location.\n"
        );

        $root = Config::getRoot();
        $fpmBin = "$root/php/$buildName/sbin/php-fpm";

        if (!file_exists($fpmBin)) {
            throw new Exception("$fpmBin doesn't exist.");
        }

        // TODO: require sudo permission
        if ($this->options->systemctl) {
            $content = $this->generateSystemctlService($buildName, $fpmBin);

            if ($this->options->stdout) {
                echo $content;
                return;
            }

            $file = '/lib/systemd/system/phpbrew-fpm.service';
            if (!is_writable(dirname($file))) {
                $this->logger->error("$file is not writable.");
                return;
            }

            $this->logger->info("Writing systemctl service entry: $file");
            file_put_contents($file, $content);

            $this->logger->info("To reload systemctl service:");
            $this->logger->info("    systemctl daemon-reload");

            $this->logger->info("Ensure that $buildName was built with --fpm-systemd option");
        } elseif ($this->options->initd) {
            $content = $this->generateInitD($buildName, $fpmBin);

            if ($this->options->stdout) {
                echo $content;
                return;
            }

            $file = '/etc/init.d/phpbrew-fpm';
            if (!is_writable(dirname($file))) {
                $this->logger->error("$file is not writable.");
                return;
            }

            $this->logger->info("Writing init.d script: $file");
            file_put_contents($file, $content);
            chmod($file, 0755); // make it executable

            $this->logger->info("To setup the startup item, remember to run update-rc.d to link the init script:");
            $this->logger->info("    sudo update-rc.d phpbrew-fpm defaults");
        } elseif ($this->options->launchctl) {
            $content = $this->generateLaunchctlService($buildName, $fpmBin);

            if ($this->options->stdout) {
                echo $content;
                return;
            }

            $file = '/Library/LaunchDaemons/org.phpbrew.fpm.plist';
            if (!is_writable(dirname($file))) {
                $this->logger->error("$file is not writable.");
                return;
            }

            $this->logger->info("Writing launchctl plist file: $file");
            file_put_contents($file, $content);

            $this->logger->info("To load the service:");
            $this->logger->info("    sudo launchctl load $file");
        } else {
            $this->logger->info(
                'Please use one of the options [--systemctl, --initd, --launchctl] to setup system fpm service.'
            );
        }
    }

    protected function generateLaunchctlService($buildName, $fpmBin)
    {
        $root   = Config::getRoot();
        $phpdir = "$root/php/$buildName";
        $config = <<<"EOS"
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
  <dict>
    <key>KeepAlive</key>
    <true/>
    <key>Label</key>
    <string>org.phpbrew.fpm</string>
    <key>ProgramArguments</key>
    <array>
        <string>$fpmBin</string>
        <string>--nodaemonize</string>
        <string>--php-ini</string>
        <string>$phpdir/etc/php.ini</string>
        <string>--fpm-config</string>
        <string>$phpdir/etc/php-fpm.conf</string>
    </array>
    <key>RunAtLoad</key>
    <true/>
    <key>LaunchOnlyOnce</key>
    <true/>
  </dict>
</plist>
EOS;
        return $config;
    }


    protected function generateSystemctlService($buildName, $fpmBin)
    {
        $root   = Config::getRoot();
        $phpdir = "$root/php/$buildName";
        $pidFile = $phpdir . '/var/run/php-fpm.pid';
        $config = <<<"EOS"
[Unit]
Description=The PHPBrew FastCGI Process Manager
After=network.target

[Service]
Type=notify
PIDFile=$pidFile
ExecStart=$fpmBin --nodaemonize --fpm-config $phpdir/etc/php-fpm.conf --pid $pidFile
ExecReload=/bin/kill -USR2 \$MAINPID

[Install]
WantedBy=multi-user.target
EOS;
        return $config;
    }


    protected function generateInitD($buildName, $fpmBin)
    {
        $root   = Config::getRoot();
        $phpdir = "$root/php/$buildName";
        $pidFile = $phpdir . '/var/run/php-fpm.pid';
        $config = <<<"EOS"
#!/bin/sh
### BEGIN INIT INFO
# Provides:          phpbrew-fpm
# Required-Start:    \$remote_fs \$network
# Required-Stop:     \$remote_fs \$network
# Default-Start:     2 3 4 5
# Default-Stop:      0 1 6
# Short-Description: starts phpbrew-fpm
# Description:       Starts The PHP FastCGI Process Manager Daemon
### END INIT INFO

PATH=/sbin:/usr/sbin:/bin:/usr/bin
DESC="PHPBrew FastCGI Process Manager"
NAME=phpbrew-fpm
PHP_VERSION=$buildName
PHPBREW_ROOT=$root
CONFFILE=$phpdir/etc/php-fpm.conf
DAEMON=$fpmBin
DAEMON_ARGS="--daemonize --fpm-config \$CONFFILE --pid $pidFile"
PIDFILE=$pidFile
TIMEOUT=30
SCRIPTNAME=/etc/init.d/\$NAME

# Exit if the package is not installed
[ -x "\$DAEMON" ] || exit 0

# Read configuration variable file if it is present
[ -r /etc/default/\$NAME ] && . /etc/default/\$NAME

# Load the VERBOSE setting and other rcS variables
. /lib/init/vars.sh

# Define LSB log_* functions.
# Depend on lsb-base (>= 3.0-6) to ensure that this file is present.
. /lib/lsb/init-functions

#
# Function to check the correctness of the config file
#
do_check()
{
    # FIXME
    # /usr/lib/php/phpbrew-fpm-checkconf || return 1
    return 0
}

#
# Function that starts the daemon/service
#
do_start()
{
	# Return
	#   0 if daemon has been started
	#   1 if daemon was already running
	#   2 if daemon could not be started
	start-stop-daemon --start --quiet --pidfile \$PIDFILE --exec \$DAEMON --test > /dev/null \
		|| return 1
	start-stop-daemon --start --quiet --pidfile \$PIDFILE --exec \$DAEMON -- \
		\$DAEMON_ARGS 2>/dev/null \
		|| return 2
	# Add code here, if necessary, that waits for the process to be ready
	# to handle requests from services started subsequently which depend
	# on this one.  As a last resort, sleep for some time.
}

#
# Function that stops the daemon/service
#
do_stop()
{
	# Return
	#   0 if daemon has been stopped
	#   1 if daemon was already stopped
	#   2 if daemon could not be stopped
	#   other if a failure occurred
	start-stop-daemon --stop --quiet --retry=QUIT/\$TIMEOUT/TERM/5/KILL/5 --pidfile \$PIDFILE --name \$NAME
	RETVAL="\$?"
	[ "\$RETVAL" = 2 ] && return 2
	# Wait for children to finish too if this is a daemon that forks
	# and if the daemon is only ever run from this initscript.
	# If the above conditions are not satisfied then add some other code
	# that waits for the process to drop all resources that could be
	# needed by services started subsequently.  A last resort is to
	# sleep for some time.
	start-stop-daemon --stop --quiet --oknodo --retry=0/30/TERM/5/KILL/5 --exec \$DAEMON
	[ "\$?" = 2 ] && return 2
	# Many daemons don't delete their pidfiles when they exit.
	rm -f \$PIDFILE
	return "\$RETVAL"
}

#
# Function that sends a SIGHUP to the daemon/service
#
do_reload() {
	#
	# If the daemon can reload its configuration without
	# restarting (for example, when it is sent a SIGHUP),
	# then implement that here.
	#
	start-stop-daemon --stop --signal USR2 --quiet --pidfile \$PIDFILE --name \$NAME
	return 0
}

do_tmpfiles() {
    local type path mode user group

    [ "\$1" != no ] && V=-v

    TMPFILES=/usr/lib/tmpfiles.d/phpbrew-fpm.conf

    if [ -r "\$TMPFILES" ]; then
	while read type path mode user group age argument; do
	    if [ "\$type" = "d" ]; then
		mkdir \$V -p "\$path"
		chmod \$V "\$mode" "\$path"
		chown \$V "\$user:\$group" "\$path"
	    fi
	done < "\$TMPFILES"
    fi
}

case "\$1" in
    start)
	if init_is_upstart; then
	    exit 1
	fi
	[ "\$VERBOSE" != no ] && log_daemon_msg "Starting \$DESC" "\$NAME"
	do_tmpfiles \$VERBOSE
	do_check \$VERBOSE
	case "\$?" in
	    0)
		do_start
		case "\$?" in
		    0|1) [ "\$VERBOSE" != no ] && log_end_msg 0 ;;
		    2) [ "\$VERBOSE" != no ] && log_end_msg 1 ;;
		esac
		;;
	    1) [ "\$VERBOSE" != no ] && log_end_msg 1 ;;
	esac
	;;
    stop)
	if init_is_upstart; then
	    exit 0
	fi
	[ "\$VERBOSE" != no ] && log_daemon_msg "Stopping \$DESC" "\$NAME"
	do_stop
	case "\$?" in
		0|1) [ "\$VERBOSE" != no ] && log_end_msg 0 ;;
		2) [ "\$VERBOSE" != no ] && log_end_msg 1 ;;
	esac
	;;
    status)
        status_of_proc "\$DAEMON" "\$NAME" && exit 0 || exit \$?
        ;;
    check)
        do_check yes
	;;
    reload|force-reload)
	if init_is_upstart; then
	    exit 1
	fi
    	log_daemon_msg "Reloading \$DESC" "\$NAME"
	do_reload
	log_end_msg \$?
	;;
    reopen-logs)
	log_daemon_msg "Reopening \$DESC logs" \$NAME
	if start-stop-daemon --stop --signal USR1 --oknodo --quiet \
	    --pidfile \$PIDFILE --exec \$DAEMON
	then
	    log_end_msg 0
	else
	    log_end_msg 1
	fi
	;;
    restart)
	if init_is_upstart; then
	    exit 1
	fi
	log_daemon_msg "Restarting \$DESC" "\$NAME"
	do_stop
	case "\$?" in
	  0|1)
		do_start
		case "\$?" in
			0) log_end_msg 0 ;;
			1) log_end_msg 1 ;; # Old process is still running
			*) log_end_msg 1 ;; # Failed to start
		esac
		;;
	  *)
	  	# Failed to stop
		log_end_msg 1
		;;
	esac
	;;
    *)
	echo "Usage: \$SCRIPTNAME {start|stop|status|restart|reload|force-reload}" >&2
	exit 1
    ;;
esac
:
EOS;
        return $config;
    }
}
