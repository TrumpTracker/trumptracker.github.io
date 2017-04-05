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
  sh "rm -rf ./_site"
  sh "bundle exec jekyll build"
  Rake::Task["json2yaml"].invoke  
  sh "bundle exec htmlproofer --allow-hash-href --check-favicon --check-opengraph --check-html --check-img-http --timeframe 15d --enforce-https --check-external-hash --url-ignore https://web.archive.org/web/* ./_site"
end