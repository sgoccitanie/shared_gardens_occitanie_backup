function stripHtml(html) {
  let tmp = document.createElement("DIV");
  tmp.innerHTML = html;
  return tmp.textContent || tmp.innerText || "";
}

function confirmGoogleDownload() {
  return confirm(
    "Ce téléchargement s'effectue depuis Google Drive. Continuer ?",
  );
}
