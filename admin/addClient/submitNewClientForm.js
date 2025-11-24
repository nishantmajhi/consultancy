async function submitFormData(formData) {
  try {
    const response = await fetch(`${window.location.origin}/api/add/client.php`, {
      method: "POST",
      body: formData,
    });

    const result = await response.json();

    if (response.ok) {
      toastDialog("success", "Client added successfully!");
      setTimeout(() => {
        window.location.href = `${window.location.origin}/admin/`;
      }, 3000);
      return true;
    } else {
      toastDialog("error", "Failed to add the client!");
      return false;
    }
  } catch (error) {
    toastDialog("error", "An error occurred while adding the client.");
    console.error("Error:", error);
    return false;
  }
}

function getFormData() {
  const form = document.forms["new_client"];

  const name = form.elements["name"].value;
  const address = form.elements["address"].value;
  const mobileNumber = form.elements["mobileNumber"].value;
  const email = form.elements["email"].value;
  const gender = form.elements["gender"].value;
  const bikeLicense = form.elements["bikeLicense"].value;
  const files = form.elements["files[]"].files;
  const profilePicture = form.elements["profilePicture"].files[0];

  const jobs = document.querySelectorAll('input[name="jobs[]"]:checked');
  let jobPreferences = Array.from(jobs).map((checkbox) => checkbox.value);

  console.log({
    name,
    address,
    mobileNumber,
    email,
    gender,
    bikeLicense,
    files,
    profilePicture,
    jobPreferences,
  });

  return {
    name,
    address,
    mobileNumber,
    email,
    gender,
    bikeLicense,
    files,
    profilePicture,
    jobPreferences,
  };
}

function buildFormData({
  name,
  address,
  mobileNumber,
  email,
  gender,
  bikeLicense,
  files,
  profilePicture,
  jobPreferences,
}) {
  let formData = new FormData();

  formData.append("name", name);
  formData.append("address", address);
  formData.append("mobileNumber", mobileNumber);
  formData.append("email", email);
  formData.append("gender", gender);
  formData.append("bikeLicense", bikeLicense);

  if (profilePicture) {
    formData.append("profilePicture", profilePicture);
  }

  jobPreferences.forEach((job) => {
    formData.append("jobPreferences[]", job);
  });

  Array.from(files).forEach((file) => {
    formData.append("files[]", file);
  });

  return formData;
}

function formIsValid(name, address, mobileNumber, files, jobPreferences, profilePicture) {
  return (
    name &&
    address &&
    mobileNumber &&
    profilePicture &&
    files.length > 0 &&
    jobPreferences.length > 0
  );
}

function submitForm() {
  document.querySelectorAll("#actions button").forEach((btn) => {
    btn.disabled = true;
  });

  const {
    name,
    address,
    mobileNumber,
    email,
    gender,
    bikeLicense,
    files,
    profilePicture,
    jobPreferences,
  } = getFormData();

  if (!formIsValid(name, address, mobileNumber, files, jobPreferences, profilePicture)) {
    alertDialog("Error",
      'Add every detail marked by <sub style="color:red; font-size:1.5rem">*</sub> before submitting the form!'
    );
    document.querySelectorAll("#actions button").forEach((btn) => {
      btn.disabled = false;
    });
    return;
  }

  const formData = buildFormData({
    name,
    address,
    mobileNumber,
    email,
    gender,
    bikeLicense,
    files,
    profilePicture,
    jobPreferences,
  });

  submitFormData(formData).then((success) => {
    if (!success) {
      document.querySelectorAll("#actions button").forEach((btn) => {
        btn.disabled = false;
      });
    }
  });
}
