# rrdwizard
RRDtool Wizard - I constantly forget the RRD command-line arguments and details, and I have to re-learn them every time I use it. Therefore, I decided to create this simple wizard which shows the basic RRD features.

The project is hosted online at http://rrdwizard.appspot.com

# TODO

This is a very old project but luckily RRDtool doesn't change its interfaces a lot, so the wizard works. Last time I used it, I got the following ideas for improvements:
- rewrite using Vue.js (static hosting?)
- step-by-step (baloon-line) navigation with the option to go click back
- checkboxes for AVERAGE, MIN, MAX, LAST
- estimated rrd file size (needs server-side support?)
- sample graphs using random data (definitely needs server-side support)
- additionally, there are a few ideas shared at the main web page of the wizard
