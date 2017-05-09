# Open data

## data.yaml

In data.yaml there is a list of promises with attributes that describe the promise.

- **title**: The title of the promise. Should not be longer than 80 characters.
- **description**: A description of the promise, formatted in HTML.
- **url**: The url is automatically generated and should not be touched
- **status**: The available statusses can be seen under 'statuses' in data.yaml
- **status_info**: Some text describing why the promise is marked how it is.
- **category**: The category of the promise. The available categories can be seen under 'icons' in data.yaml
- **tags**: An array of some tags describing the promise. Not really used anymore.
- **comments**: An array of shorted url's that point to the comments in Reddit. Sorted by time, first is newest.
- **sources**: An array of sources that correlate to the promise. Sorted by time, last is newest.

## data.json

data.json is being generated automatically and should **not** be changed!

# API

Both data files are identical. Please note that Github is rate limiting you and you can not make more than 60 requests per hour if you're [unauthenticated](https://developer.github.com/v3/#authentication). If you're [authenticated](https://developer.github.com/v3/#authentication), you can make up to 5000 requests per hour.

## YAML

If you prefer getting the data in YAML, make API requests to https://raw.githubusercontent.com/TrumpTracker/trumptracker.github.io/master/_data/data.yaml

## JSON

If you prefer getting the data in JSON, make API requests to https://raw.githubusercontent.com/TrumpTracker/trumptracker.github.io/master/_data/data.json

