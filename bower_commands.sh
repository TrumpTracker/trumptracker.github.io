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
rid=$(python2 -c "import random,string;print((lambda length:''.join(random.choice(string.lowercase) for i in range(length)))(10))")
# echo "rid $rid" | indent | indent
sasslocation="./css/styles${rid}.sass" ## sass  location
finlocation="/tmp/${rid}styles.css"   ## final location

# jekyll is gonna be yuge
echo "Making $sasslocation" | indent | indent
tail -n +3 ./css/styles.sass > $sasslocation

# sass compilation step
echo "Making $finlocation"  | indent | indent
sass $sasslocation -I ./css/partials > "$finlocation"
rm $sasslocation

# actually concatenating things
csspackage="./assets/css.css"
touch                          $csspackage
cat /dev/null                > $csspackage
printf "/* Bootstrap */\n"  >> $csspackage
cat ./bower_components/bootstrap/dist/css/bootstrap.min.css >> $csspackage
printf "/* Custom    */\n"  >> $csspackage
cat $finlocation            >> $csspackage

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

echo "Moving fonts"
echo "cp ./bower_components/bootstrap/dist/fonts ./fonts -r" | indent
cp ./bower_components/bootstrap/dist/fonts ./fonts -R || echo "moving fonts failed"
echo "done"
echo
