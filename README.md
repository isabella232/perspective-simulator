Testing simulator with commenting API:
```
$ git clone git@gitlab.squiz.net:perspective/Simulator.git PerspectiveSimulator
Cloning into 'PerspectiveSimulator'...

$ git clone git@gitlab.squiz.net:gsherwood/perspective-commenting-sim.git CommentingAPI
Cloning into 'CommentingAPI'...

$ cd CommentingAPI

$ composer install
Loading composer repositories with package information
Updating dependencies (including require-dev)
Package operations: 1 install, 0 updates, 0 removals
  - Installing perspective/simulator (dev-master): Mirroring from ../PerspectiveSimulator
Writing lock file
Generating autoload files

$ php example.php
```

To start the web server:
```
php -S localhost:8000 vendor/Perspective/Simulator/src/Router.php
```
