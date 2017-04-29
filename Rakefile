# coding: utf-8
require 'jekyll'

# for yaml2json
require 'json'
require 'yaml'

# Extend string to allow for bold text.
class String
  def bold
    "\033[1m#{self}\033[0m"
  end
end

# Rake Jekyll tasks
task :build do
  Rake::Task["json2yaml"].invoke
  puts 'Building site...'.bold
  Jekyll::Commands::Build.process(profile: true)
end

task :clean do
  puts 'Cleaning up _site...'.bold
  Jekyll::Commands::Clean.process({})
end

task :serve do
  Rake::Task["json2yaml"].invoke
  puts 'Autoregenerating site...'.bold
  sh "bundle exec jekyll serve"
end

task :json2yaml do
  input_filename = "./_data/data.yaml"
  output_filename = input_filename.sub(/(yml|yaml)$/, 'json')

  input_file = File.open(input_filename, 'r')
  input_yml = input_file.read
  input_file.close

  output_json = JSON.dump(YAML::load(input_yml))
  output_file = File.open(output_filename, 'w+')
  output_file.write(output_json)
  output_file.close
  puts 'data.json successfully created.'.bold
end


task :test do
  require 'html-proofer'
  sh "rm -rf ./_site"
  sh "bundle exec jekyll build"
  Rake::Task["json2yaml"].invoke

  options = {
    :allow_hash_href => true,
    :check_favicon => true,
    :check_opengraph => true,
    :check_html => true,
    :check_img_http => true,
    :cache => { :timeframe => '15d' },
    :enforce_https => true,
    :check_external_hash => true,
    :url_ignore => [/https:\/\/web.archive.org\/web\/*\//]
  }
  HTMLProofer.check_directory("./_site", options).run
end
