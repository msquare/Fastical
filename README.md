# Fastical

A fast PHP ical parser for vevents.

## Requirements
 * PHP > 5.5
 * Composer

## Setup
 * `git submodule init`
 * `git submodule update`
 * In `simshaun_recurr` do `composer install`
 
## Usage

Get events from the next 7 days:
```
$f = new Fastical('events.ics');
print_r($f->getEvents(time(), time() + 7 * 24 * 60 * 60));
```
