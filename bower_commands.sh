#!/usr/bin/env bash

indent () { sed 's/^/  /'; }

echo "Installing Bower Components"
rm -rf ./bower_components
bower install

echo
echo "Assembling CSS and JS Assets"

echo "In directory: $PWD" \
  | indent
script_dir="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
echo "Script Location: $script_dir" \
  | indent | indent
echo "Script Name: $0" \
  | indent | indent

echo
echo
echo "Concatenating CSS:" | indent
echo "Files (in order): ('<!-- Latest compiled and minified CSS -->')" \
  | indent | indent
echo "Bootstrap Minified CSS
Custom Stylesheet" \
  | indent | indent | indent

# make custom stylesheet
## random id
rid=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
# echo "rid $rid" | indent | indent
## unique location
uloc="/tmp/${rid}styles.css"
echo "Making $uloc" \
  | indent | indent
# sass compilation step
tail -n +3 ./css/styles.sass | sass > "$uloc"

# actually concatenating things
csspackage="./assets/css.css"
touch                          $csspackage
cat /dev/null                > $csspackage
printf "/* Bootstrap */\n"  >> $csspackage
cat ./bower_components/bootstrap/dist/css/bootstrap.min.css >> $csspackage
printf "/* Custom    */\n"  >> $csspackage
cat $uloc                   >> $csspackage

echo
echo "Successfully made $csspackage." \
  | indent | indent
echo

echo "Concatenating JS:" | indent
echo "Order Summary:" | indent | indent
echo "
<!-- Font Awesome -->
<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<!-- Latest compiled and minified JavaScript --> (bootstrap)" \
  | indent | indent | indent

echo "File Sources:" | indent | indent


# actually concatenating things
jspackage="./assets/js.js"
touch                            $jspackage
cat /dev/null                  > $jspackage
printf "/* Font Awesome */\n" >> $jspackage
curl -s use.fontawesome.com/1e8cbd500f.js >> $jspackage
printf "/* jQuery    */\n"    >> $jspackage
curl -s ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js >> $jspackage
printf "/* Bootstrap */\n"    >> $jspackage
cat ./bower_components/bootstrap/dist/js/bootstrap.min.js >> $jspackage
printf "/* Custom    */\n"    >> $jspackage

echo
echo "Successfully made $jspackage." \
  | indent | indent
echo

echo "Done Assembling Assets"
