document.addEventListener("DOMContentLoaded", () => {

  const wrapper_filters = document.querySelector('#wrapper-calculator-filters'),
    wrapper_insert_table = document.querySelector('[data-ajaxurl]'),
    ajaxurl = wrapper_insert_table.getAttribute('data-ajaxurl');

  //set the data for the backend. Data is generated from frontend filters
  //key, value - optional parameters
  function setFormData(key, value) {
    let all_filters = wrapper_filters.querySelectorAll('[data-trader-info]'),
      formData = new FormData();

    formData.append('action', 'trader-filter');
    all_filters.forEach(item => {
      formData.append(item.getAttribute('data-trader-info'), item.value);
    });

    if (!(key === undefined)) {
      formData.append(key, value);
    }

    return formData;
  };

  //send data to the backend. Response display on the frontend
  //data is what the setFormData function returns
  function insert_data(data) {
    let formData = data;

    fetch(ajaxurl, {
      method: 'POST',
      body: formData,
    })
      .then((response) => response.text())
      .then((response) => {
        wrapper_insert_table.innerHTML = response;
      });
  }

  // creating the first display of the table on the frontend
  insert_data(setFormData());

  //looking at changing filters on the frontend.
  //When the filter changes, run the insert_data function
  wrapper_filters.addEventListener('change', (event) => {
    if (event.target && event.target.tagName == "INPUT") {
      insert_data(setFormData())
    }
  })

  //creating ajax pagination for the table
  wrapper_insert_table.addEventListener('click', (event) => {
    if (event.target && event.target.classList.contains('page-numbers')) {

      event.preventDefault();
      const link = event.target;
      let page;

      window.scrollTo({
        top: 60,
        behavior: "smooth"
      });

      if (link.classList.contains('prev')) {
        page = +document.querySelector('.page-numbers.current').textContent - 1;
      } else if (link.classList.contains('next')) {
        page = +document.querySelector('.page-numbers.current').textContent + 1;
      } else {
        page = link.textContent;
      }

      let data = setFormData('current_page', page);
      insert_data(data);
    };
  });
});