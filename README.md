# DND Character Sheet Generator

A script to generate a character data format from different importers.
It is primarily built to create character sheets, but you can also use
it to create a unified format for different importers.

> **NOTICE**
> This is still a work in progress. Processing of the DNDBeyond API
> response has been done by trial and error and may not reflect
> your character as seen on DNDBeyond. Keep this in mind when you
> use it to create character sheets.

## Usage

To generate a character sheet you can use the `from-api` command:

    $ bin/dndcharacter dndbeyond --characterid %character-id

You can also generate a file based off of a JSON file containing the
API response:

    $ bin/dndcharacter dndbeyond -f %path-to-file

You can also get the data in JSON format by using the `--json` flag:

    $ bin/dndcharacter dndbeyond --characterid %character-id --json

## Contributing

You can find helper scripts within `composer.json` that is used to
validate the code (check code style, unit tests etc). To run all
validation steps you can make use of the `review` command.

    $ composer run review
