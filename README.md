Change blindness test
=====================
This is a small web application that can be used to test change blindness. It was developed for 
an assignment in the Visual Communication Design course on the Design for Interaction master at 
the [Delft University of Technology] by [Raymond Jelierse].

Requirements
------------
*  A webserver with the following packages:
   * PHP 5.3.x
   * MySQL 5.x
   * [Twig] 1.6
*  Pairs of images to test with.
   The filenames should have the following format: `<phase>-(with|without).png`.
   You can configure the phases in the `settings.json` file.

Set up
------
1. Place the change blindness test somewhere in your webroot.
2. Edit the settings file to your needs.
3. Add the images to the `img` directory.
4. Go to `<your web path>/install/` and follow the on screen instructions.

Acknowledgements
----------------
This project includes code from [Bootstrap].

[Bootstrap]: http://twitter.github.com/bootstrap/
[Delft University of Technology]: http://home.tudelft.nl/
[Raymond Jelierse]: http://www.raymondjelierse.nl/