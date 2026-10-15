document.addEventListener("DOMContentLoaded", () => {
  const jobList = document.getElementById("job_list");
  jobList.innerHTML = "";

  jobs.forEach((job) => {
    const option = document.createElement("option");
    option.value = job;
    jobList.appendChild(option);
  });
});

function updateJobList() {
  const jobInput = document.getElementById("job_preferences");
  const selectedJob = jobInput.value.trim();

  if (selectedJob !== "") {
    if (!getExistingJobs().includes(selectedJob)) createCheckBox(selectedJob);
  }

  jobInput.value = "";
}

function getExistingJobs() {
  return Array.from(
    document.querySelectorAll('input[type="checkbox"][name="jobs[]"]')
  ).map((checkbox) => checkbox.value);
}

function createCheckBox(selectedJob) {
  const chosenJobs = document.getElementById("chosen_jobs");
  chosenJobs.style.display = "flex";

  const labelElement = createJobLabel(selectedJob, chosenJobs);
  const inputElement = createJobInput(selectedJob);

  labelElement.appendChild(inputElement);
  chosenJobs.appendChild(labelElement);
}

function createJobLabel(selectedJob, chosenJobs) {
  const labelElement = document.createElement("label");
  labelElement.className = "job";
  labelElement.textContent = selectedJob;

  labelElement.addEventListener("click", () => {
    labelElement.remove();

    if (getExistingJobs().length === 0) {
      chosenJobs.style.display = "none";
    }
  });

  return labelElement;
}

function createJobInput(selectedJob) {
  const inputElement = document.createElement("input");
  inputElement.type = "checkbox";
  inputElement.checked = true;
  inputElement.name = "jobs[]";
  inputElement.value = selectedJob;

  return inputElement;
}

const jobs = [
  "Teacher",
  "Programmer",
  "Website Designer",
  "Backend Developer",
  "Frontend Developer",
  "Data Scientist",
  "Machine Learning Engineer",
  "Product Manager",
  "Graphic Designer",
  "Digital Marketer",
  "Content Writer",
  "UI/UX Designer",
  "Network Administrator",
  "Database Administrator",
  "Cybersecurity Analyst",
  "Cloud Architect",
  "Mobile App Developer",
  "Game Developer",
  "Business Analyst",
  "DevOps Engineer",
];
