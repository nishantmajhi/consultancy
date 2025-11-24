function generateMOU(uuid) {
  const params = new URLSearchParams({ uuid });
  window.open(`${window.location.origin}/gen/mou.php?${params.toString()}`, '_blank');
}

function generateCV(uuid) {
  const params = new URLSearchParams({ uuid });
  window.open(`${window.location.origin}/gen/cv.php?${params.toString()}`, '_blank');
}
