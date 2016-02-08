cd ../

VERSION="$1"

export COPYFILE_DISABLE=true

tar -zcvf "$VERSION.tar.gz" "$VERSION" description.*

cd repo

echo
echo "Successfully created $VERSION.tar.gz"
echo

