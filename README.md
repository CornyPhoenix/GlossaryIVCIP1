# Glossary for IVC and IP1

This is a glossary for IVC and IP1.


## Generate the Glossary

First of all, you need PHP 5.5 or higher and to get [Composer](https://getcomposer.org/):

```
curl -sS https://getcomposer.org/installer | php
```

Then, you can use the PHP-script to generate the Wiki and the LaTeX include file:

```
php src/main.php
```

To generate the glossary with LaTeX, use

```
xelatex glossary 
makeglossaries glossary 
xelatex glossary 
open glossary.pdf
```

([XeLaTeX](https://de.wikipedia.org/wiki/XeTeX) comes with most LaTeX distributions.)

The wiki gets dumped to `build/wiki/`. When it gets pushed to Github, it can be displayed there.


## How to Contribute

You need to edit the glossary-file located in `src/glossary.txt`.

### Main Defintions

The language is as follows:

```
Key-Name: #Some #Tags #And #Some !images !located !in !img !folder
  Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor 
  invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam 
  et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est 
  Lorem ipsum dolor sit amet.
```

So the body of a defintion is indented with a Tab ("\t").

### References

To make references, use an arrow symbol `=>` and write the referenced key afterwards. You can use sqaured brackets (`[...]`) to add some displayed text and curly brackets (`{...}`) to add text to referenced Key (won't be displayed).

### Empty Definitions

Just write a hyphen (`-`), to leave a definition empty (you can still use tags and images, though).

```
Empty Key: - #Some #Tags !empty !image
```
