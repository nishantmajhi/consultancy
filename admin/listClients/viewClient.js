function renderClientInfo(clientData) {
  const dialog = document.createElement('dialog');
  dialog.id = 'info';

  const figure = document.createElement('figure');
  figure.id = 'client';

  const img = document.createElement('img');
  // img.src = clientData.profilePic.replace(double backslashes with a single forward slash);
  img.src = '../../uploads/defaultUser.webp';
  img.alt = clientData.id;
  img.loading = 'lazy';
  figure.appendChild(img);

  const figcaption = document.createElement('figcaption');

  const dl = document.createElement('dl');

  const dt = document.createElement('dt');
  dt.textContent = clientData.name;
  dl.appendChild(dt);

  const dd = document.createElement('dd');
  const ul = document.createElement('ul');

  const address = document.createElement('li');
  address.textContent = clientData.address;
  ul.appendChild(address);

  const email = document.createElement('li');
  email.textContent = clientData.email || '';
  ul.appendChild(email);

  const mobileNumber = document.createElement('li');
  mobileNumber.textContent = '+977 ' + clientData.mobileNumber;
  ul.appendChild(mobileNumber);

  dd.appendChild(ul);
  dl.appendChild(dd);
  figcaption.appendChild(dl);

  const preferredJobs = document.createElement('div');
  preferredJobs.id = 'preferred_jobs';
  for (let i = 0; i < clientData.jobPreferences.length; i++) {
    if (i < clientData.jobPreferences.length - 1) {
      preferredJobs.innerHTML +=
        '<span class="job">' +
        clientData.jobPreferences[i].toLowerCase() +
        "</span>,&nbsp;";
    } else {
      preferredJobs.innerHTML +=
        '<span class="job">' + clientData.jobPreferences[i].toLowerCase() + "</span>";
    }
  }
  figcaption.appendChild(preferredJobs);

  figure.appendChild(figcaption);
  dialog.appendChild(figure);

  const fieldset = document.createElement('fieldset');
  const legend = document.createElement('legend');
  legend.textContent = 'Documents & Signatures';
  fieldset.appendChild(legend);

  const docContainer = document.createElement('div');
  docContainer.id = 'documents_container';
  
  let docPaths = JSON.parse(clientData.documents);
  docPaths.forEach(docPath => {
    const img = document.createElement('img');
    // img.src = docPath.replace(double backslashes with a single forward slash);
    img.src = '../../uploads/defaultDoc.jpg';
    img.alt = "Document";
    img.loading = 'lazy';
    img.className = 'document-image';
    docContainer.appendChild(img);
  });
  fieldset.appendChild(docContainer);

  dialog.appendChild(fieldset);

  const actions = document.createElement('div');
  actions.id = 'actions';

  const btnMOU = document.createElement('button');
  btnMOU.textContent = 'Create MOU';
  btnMOU.className = 'green_button transparent_button';
  btnMOU.onclick = () => generateMOU(clientData.id);
  actions.appendChild(btnMOU);

  const btnCV = document.createElement('button');
  btnCV.textContent = 'Generate CV';
  btnCV.className = 'green_button transparent_button';
  btnCV.onclick = () => generateCV(clientData.id);
  actions.appendChild(btnCV);

  const btnApply = document.createElement('button');
  btnApply.textContent = 'Apply for Job';
  btnApply.className = 'negative_button confirm_button';
  btnApply.onclick = () => applyForJob(clientData.id);
  actions.appendChild(btnApply);
  
  const btnClose = document.createElement('button');
  btnClose.textContent = 'Close Dialog';
  btnClose.className = 'positive_button transparent_button';
  btnClose.onclick = () => dialog.close();
  actions.appendChild(btnClose);
  btnClose.autofocus = true;

  dialog.appendChild(actions);

  return dialog;
}

function showClient(id) {
  clientData = clients.find(client => client.id === id);
  
  dialog = renderClientInfo(clientData);
  document.body.appendChild(dialog);
  
  dialog.showModal();
}
