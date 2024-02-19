# DND Beyond Character Sheet

A script to generate a character sheet from DNDBeyond character API
data. It can also be used to get the API data from DNDBeyond in a more
processed matter.

> **NOTICE**
> This is still a work in progress. Processing of the API response has
> been done by trial and error and may not reflect your character as
> seen on DNDBeyond. Keep this in mind when you use it to create
> character sheets.

## Usage

To generate a character sheet you can use the `from-api` command:

    $ composer run dndb-api %character-id

You can also generate a file based off of a JSON file containing the
API response:

    $ composer run dndb-file %path-to-file

You can also get the data in JSON format by using the `--json` flag:

    $ composer run dndb-api -- --json %character-id

## Contributing

You can find helper scripts within `composer.json` that is used to
validate the code (check code style, unit tests etc). To run all
validation steps you can make use of the `review` command.

    $ composer run review
