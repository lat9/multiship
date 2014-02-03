<?php
?>
<script type="text/javascript">
var canleave = true;
function ok2leave() {
  canleave = true;
}
function notok2leave() {
  canleave = false;
}
function goodbye(e) {
  if (!e) e = window.event;
  
  if (!canleave) {
    //e.cancelBubble is supported by IE - this will kill the bubbling process.
    e.cancelBubble = true;
    e.returnValue = 'You sure you want to leave?'; //This is displayed on the dialog

    //e.stopPropagation works in Firefox.
    if (e.stopPropagation) {
      e.stopPropagation();
      e.preventDefault();
    }
  }
}
window.onbeforeunload=goodbye;
</script>