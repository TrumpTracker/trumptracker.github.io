# gem install twitter
require 'twitter'
while true
    begin
        # configure with keys from https://apps.twitter.com/
        config = {
            consumer_key:        '',
            consumer_secret:     '',
            access_token:        '',
            access_token_secret: ''
        }
        rClient = Twitter::REST::Client.new config
        sClient = Twitter::Streaming::Client.new(config)

        topics = %w(#TrumpTracker trumptracker.github.io)
        sClient.filter(:track => topics.join(',')) do |tweet|
            if tweet.is_a?(Twitter::Tweet)
              puts tweet.text 
              rClient.fav tweet
            end
        end
    rescue
        puts 'error occurred, waiting for 5 seconds'
        sleep 5
    end
end