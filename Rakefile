# coding: utf-8
require 'jekyll'

# for yaml2json, generatestatic and generateurls
require 'json'
require 'yaml'

# for parsing source titles
#require 'openssl'
#require 'open-uri'
require 'metainspector'

# Extend string to allow for bold text.
class String
	def bold
		"\033[1m#{self}\033[0m"
	end
	def prettyurl
		"#{self}".downcase.strip.gsub('-', '').gsub(' ', '-').gsub(/[^\w-]/, '').gsub('--', '-')[0..100]
	end
end

# Rake Jekyll tasks
task :build do
	Rake::Task["generateurls"].invoke
	Rake::Task["generatestatic"].invoke
	Rake::Task["yaml2json"].invoke
	puts 'Building site...'.bold
	Jekyll::Commands::Build.process(profile: true)
end

task :clean do
	puts 'Cleaning up _site...'.bold
	Jekyll::Commands::Clean.process({})
end

task :serve do
	Rake::Task["generateurls"].invoke
	Rake::Task["generatestatic"].invoke
	Rake::Task["yaml2json"].invoke
	puts 'Autoregenerating site...'.bold
	sh "bundle exec jekyll serve"
end

task :generatestatic do
	puts 'Generating unique promise pages...'.bold
	layout_file = File.open("./_layouts/promise.html", 'r');
	layout_template = layout_file.read
		yaml_file = File.open("./_data/data.yaml", 'r');
	yaml = yaml_file.read
	yaml = YAML::load(yaml)
	Pathname.new("./promises/").children.each { |p| p.unlink }
	yaml['promises'].each_with_index {
		|x, index|
		title = x['title'];
		status = x['status'];
		comments = x['comments']
		category = x['category']
		description = x['description']
		filename = title.prettyurl
		out_file = File.new("./promises/#{filename}.html", "w+")
		layout = "---\nlayout: page\npermalink: /:basename/\n---\n"
		layout << layout_template
		$sources = ''
		x['sources'].each {
			|y|
			#begin
            #    srctitle = MetaInspector.new(y, faraday_options: { ssl: { verify: false } }).title
			#	if srctitle == '' || srctitle.length < 5 || srctitle.length > 200
			#        srctitle = y
			#    end
			#rescue Exception => e  
            #    puts e.message
				srctitle = y
            #ensure
			#    puts srctitle
			    if srctitle.include? "https://web.archive.org/web/"
				    srctitle = srctitle.gsub "https://web.archive.org/web/", ""
					srctitle = srctitle.split("/")
					srctitle.shift()
					srctitle = srctitle.join("/")
				end
			    $sources << "<li><a class='src' target='_blank' href='#{y}'>#{srctitle}</a></li>\n";			
            #end  
		}
		tweettext = '@realDonaldTrump '
		if status == "Not started"
			tweettext << "hasn't started"
			statuscolor = '#31708f'
		elsif status == "In progress"
			tweettext << "is progressing"
			statuscolor = '#8a6d3b'
		elsif status == "Achieved"
			tweettext << "achieved"
			statuscolor = '#5cb85c'
		elsif status == "Broken"
			tweettext << "broke"
			statuscolor = '#d9534f'
		elsif status == "Compromised"
			tweettext << "compromised on"
			statuscolor = '#4e5459'
		end
		tweettext << " promise no. #{index}: #{title}"
		tweettext_short = "#{tweettext}"[0..98]
		if tweettext.length > 98
			tweettext_short << "..."
		end
		tweettext = CGI.escape(tweettext_short)
		url = title.prettyurl
		titleurl = CGI.escape(title)
		layout.gsub! "<ul class='sources'>", "<ul class='sources'>\n#{$sources}"
		layout.gsub! "{{ page.title }}", title
		layout.gsub! "{{ page.titleurl }}", titleurl
		layout.gsub! "{{ page.url }}", url
		layout.gsub! "{{ page.statuscolor }}", statuscolor
		layout.gsub! "{{ page.status }}", status
		layout.gsub! "{{ page.description }}", description
		layout.gsub! "{{ tweettext }}", tweettext
		layout.gsub! "{{ page.comments }}", comments
		comments.sub! "https://redd.it/", ""
		layout.gsub! "{{ page.commentsid }}", comments
		layout.gsub! "{{ page.category }}", category
		out_file.puts(layout)
		out_file.close
	}
	yaml_file.close
	layout_file.close
	puts 'Done generating promise pages.'.bold
end

task :generateurls do
	yaml_file = File.open("./_data/data.yaml", 'r');
	yaml = yaml_file.read
	fullyaml = ''
	yaml = yaml.split("    -")
	yaml.each_with_index {
		|x, index|
		if x.include? "title: '"
			title = x.split("title: '")[1].split("'\n")[0]
			url = "url: 'https://trumptracker.github.io/"
			url << title.prettyurl
			url << "/'"
			oldurl = "url: '"
			oldurl << x.split("url: '")[1].split("'\n")[0]
			oldurl << "'"
			x.sub! oldurl, url
		end
		fullyaml << x
		if index != yaml.size - 1
			fullyaml << "    -"
		end
	}
	yaml_file.close
	output_file = File.open("./_data/data.yaml", 'w+')
	output_file.write(fullyaml)
	output_file.close
end

task :yaml2json do
	puts 'Converting YAML to JSON...'.bold
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
	Rake::Task["generateurls"].invoke
	Rake::Task["generatestatic"].invoke
	Rake::Task["yaml2json"].invoke
	options = {
		:allow_hash_href => true,
		:check_favicon => true,
		:check_opengraph => true,
		:check_html => true,
		:check_img_http => true,
		:cache => { :timeframe => '15d' },
		:enforce_https => true,
		:check_external_hash => true,
		:url_ignore => [/https:\/\/web.archive.org\/web\//]
	}
	HTMLProofer.check_directory("./_site", options).run
end