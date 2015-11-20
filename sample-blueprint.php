<?php if(!defined('KIRBY')) exit ?>

title: Redirects
pages: false
files: 
  type:
    - code
    - document
fields:
  title:
    label: Title
    type: text
  redirects:
    label: Redirects
    type: structure
    style: table
    fields:
      old:
        label: Old URL
        type: text
        icon: times
      new:
        label: New URL
        type: text
        icon: check
      external:
        label: External
        text: Is it an external link?
        type: checkbox
        icon: share