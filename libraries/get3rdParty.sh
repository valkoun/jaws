set -e
## Highlight:
wget http://www.jaws-project.com/data/files/develExtras/GeSHi-1.0.7.5.tar.gz
tar zxvf GeSHi-1.0.7.5.tar.gz
rm -rf GeSHi-1.0.7.5.tar.gz
cd geshi
rm -rf docs/ contrib/
cd ..

# # RSS Parser (magpierss)
# wget http://www.jaws-project.com/data/files/develExtras/magpierss-0.61.tar.gz
# tar zxvf magpierss-0.61.tar.gz
# mv magpierss-0.61 magpierss
# rm -rf magpierss-0.61.tar.gz
# cd magpierss
# sed -e "s/\$this->channel\['tagline'\] = \$this->channel\['description'\];/\$this->channel\['description'\] = isset (\$this->channel\['tagline'\]) \? \$this->channel\['tagline'\] : \"\";\/\/Changed to validation/g" < rss_parse.inc > rss_parse.back
# mv rss_parse.back rss_parse.inc
# sed -e "s/\$this->channel\['description'\] = \$this->channel\['tagline'\];/\$this->channel\['description'\] = isset (\$this->channel\['tagline'\]) \? \$this->channel\['tagline'\] : \"\";\/\/Changed to validation/g" < rss_parse.inc > rss_parse.back
# mv rss_parse.back rss_parse.inc
# sed -e 's/\$rss->etag and \$rss->last_modified/is_object (\$rss) and $rss->etag and \$rss->last_modified/g' < rss_fetch.inc > rss_fetch.back
# mv rss_fetch.back rss_fetch.inc
# sed -e "s/\tvar \$read_timeout\t=\t0;/\tvar \$read_timeout\t=\t5;/g" < extlib/Snoopy.class.inc > extlib/Snoopy.class.old
# mv extlib/Snoopy.class.old extlib/Snoopy.class.inc
# sed -e "s/\tvar \$timed_out\t\t=\tfalse;/\tvar \$timed_out\t\t=\ttrue;/g" < extlib/Snoopy.class.inc > extlib/Snoopy.class.old
# mv extlib/Snoopy.class.old extlib/Snoopy.class.inc
# sed -e "s/\t\t\$this->timed_out = false;/\t\t\$this->timed_out = true;/g" < extlib/Snoopy.class.inc > extlib/Snoopy.class.old
# mv extlib/Snoopy.class.old extlib/Snoopy.class.inc
# sed -e "s/\tvar \$_fp_timeout\t=\t30;/\tvar \$_fp_timeout\t=\t5;/g" < extlib/Snoopy.class.inc > extlib/Snoopy.class.old
# mv extlib/Snoopy.class.old extlib/Snoopy.class.inc
# cat > jawsmagpierss.php << "EOF"
# <?php
# define ('MAGPIE_CACHE_DIR', 'data/'.'rsscache');
# define ('MAGPIE_DIR', JAWS_PATH.'/include/Extras/magpierss/');
# require_once (JAWS_PATH.'include/Extras/magpierss/rss_fetch.inc');
# ?>
# EOF
# cd ..

# Script.aculo.us; when new upstream version is released, update the version here
SCRIPTACULOUS_VERSION=1.6.5
SCRIPTACULOUS_URL=http://script.aculo.us/dist/
SCRIPTACULOUS_FILE="scriptaculous-js-${SCRIPTACULOUS_VERSION}.tar.gz"

wget "${SCRIPTACULOUS_URL}/${SCRIPTACULOUS_FILE}"
tar zxvf $SCRIPTACULOUS_FILE
if [ ! -d `pwd`/scriptaculous ]; then
    mkdir scriptaculous
else
    rm -rf scriptaculous/*.js
    rm -rf scriptaculous/MIT-LICENSE
fi

# Create a Prototype dir one dir up
if [ ! -d `pwd`/prototype ]; then
    mkdir prototype
else
    rm -rf prototype/prototype.js
fi

mv scriptaculous-js-${SCRIPTACULOUS_VERSION}/src/*.js scriptaculous/
mv scriptaculous-js-${SCRIPTACULOUS_VERSION}/MIT-LICENSE scriptaculous/
# We'll always use the same lib for the whole Jaws as is used in scriptaculous
mv scriptaculous-js-${SCRIPTACULOUS_VERSION}/lib/prototype.js prototype/
rm -rf scriptaculous-js-${SCRIPTACULOUS_VERSION}*

#IE JS lib
wget http://belnet.dl.sourceforge.net/sourceforge/ie7/IE7_0_9.zip
unzip IE7_0_9.zip
if [ ! -d `pwd`/IE7 ]; then
    mkdir IE7
fi
mv IE7_0_9/* IE7
rm -rf IE7/test*
rm -rf IE7_0_9*
