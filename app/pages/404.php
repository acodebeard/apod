<?php
$messages = [
  "This page has slipped beyond the bounds of Laniakea.",
  "404: This page is drifting in the inter-supercluster void.",
  "Trajectory lost beyond the Hercules–Corona Borealis Great Wall.",
  "This content has redshifted out of observable range.",
  "We aimed for Virgo. We ended up... nowhere.",
  "This page fell into a data singularity. Try again.",
  "Page not found — possibly caught by the Great Attractor.",
  "Your request was ejected at relativistic speed.",
  "Dark energy repelled this page into the unknown.",
  "Signal lost in cosmic microwave background noise."
];

$randomMessage = $messages[array_rand($messages)];

$linkTexts = [
  "Navigate back to charted space",
  "Return to the APOD archive",
  "Exit the singularity",
  "Re-enter normal spacetime",
  "Back to the stars",
  "Abort mission, head for home"
];

$randomLink = $linkTexts[array_rand($linkTexts)];


?>
<div class="message">
  <?= htmlspecialchars($randomMessage) ?><br>
  Please try again.<br>
  <a href="/apod/"><?= htmlspecialchars($randomLink) ?></a>
</div>