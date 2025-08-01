DST_DIR=$1

if [ ! -d "$DST_DIR" ]; then echo "$DST_DIR missing."; fi

# Get the version name.
NAME=$(sed -nE 's/<name>([^<]+)<\/name>/\1/p' package-info.xml | awk '{$1=$1};1')
NAME=`echo $NAME | sed -e "s/ /-/g"`

# Get the version and clean it up.
VERSION=$(sed -nE 's/<version>([^<]+)<\/version>/\1/p' package-info.xml | awk '{$1=$1};1' | awk '{for(i=1;i<=NF;i++){ $i=toupper(substr($i,1,1)) substr($i,2) }}1')
VERSION=`echo $VERSION | sed -e "s/ /-/g"`
VERSION=`echo $VERSION | sed -e "s/Rc/RC/g"`

if [ -z "${VERSION}" ]; then
  echo "Version is missing"
  exit 1;
fi

if [ -z "${NAME}" ]; then
  echo "Name is missing"
  exit 1;
fi

BASE_FILE="${NAME}_${VERSION}"

# Tar with gz
if [ "$(uname)" == "Darwin" ]; then
	tar --no-xattrs --no-acls --no-mac-metadata --no-fflags --exclude='.git' --exclude='screenshots' --exclude='vendor' --exclude='.*' --exclude=\'composer.*\' -czf $DST_DIR/$BASE_FILE.tgz *
else
	tar --no-xattrs --no-acls--exclude='.git' --exclude='screenshots' --exclude='vendor' --exclude='.*' --exclude=\'composer.*\' -czf $DST_DIR/$BASE_FILE.tgz *
fi

# Zip
zip -x ".git" "screenshots/*" "vendor/*"  ".*/" "composer.*"  -1 $DST_DIR/$BASE_FILE.zip -r *