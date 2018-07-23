scriptsDir=$(dirname "$(readlink -f "$0")")
. $scriptsDir/getProjectData.sh

touch /data/nfs/$projectName/logs/terminal && chmod 666 /data/nfs/$projectName/logs/terminal && truncate --size 0 /data/nfs/$projectName/logs/terminal
trap "rm /data/nfs/$projectName/logs/terminal" EXIT INT TERM
tail -f /data/nfs/$projectName/logs/terminal
