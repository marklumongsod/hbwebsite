let add_pool_form = document.getElementById('add_pool_form');

add_pool_form.addEventListener('submit', function (e) {
  e.preventDefault();
  add_pool();
});

function add_pool() {
  let data = new FormData();
  data.append('add_pool', '');
  data.append('name', add_pool_form.elements['name'].value);
  data.append('description', add_pool_form.elements['description'].value);
  data.append('price', add_pool_form.elements['price'].value);

  let xhr = new XMLHttpRequest();
  xhr.open("POST", "ajax/pools.php", true);

  xhr.onload = function () {
    var myModal = document.getElementById('add-pool');
    var modal = bootstrap.Modal.getInstance(myModal);
    modal.hide();

    if (this.responseText == 1) {
      alert('success', 'New pool added!');
      add_pool_form.reset();
      get_all_pools();
    } else {
      alert('error', 'Server Down!');
    }
  }

  xhr.send(data);
}

function get_all_pools() {
  let xhr = new XMLHttpRequest();
  xhr.open("POST", "ajax/pools.php", true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

  xhr.onload = function () {
    document.getElementById('pool-data').innerHTML = this.responseText;
  }

  xhr.send('get_all_pools');
}

let edit_pool_form = document.getElementById('edit_pool_form');

function edit_details(id) {
  let xhr = new XMLHttpRequest();
  xhr.open("POST", "ajax/pools.php", true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

  xhr.onload = function () {
    let data = JSON.parse(this.responseText);
    edit_pool_form.elements['name'].value = data.pooldata.name;
    edit_pool_form.elements['description'].value = data.pooldata.description;
    edit_pool_form.elements['price'].value = data.pooldata.price;
    edit_pool_form.elements['pool_id'].value = data.pooldata.id;
  }

  xhr.send('get_pool=' + id);
}

edit_pool_form.addEventListener('submit', function (e) {
  e.preventDefault();
  submit_edit_pool();
});

function submit_edit_pool() {
  let data = new FormData();
  data.append('edit_pool', '');
  data.append('pool_id', edit_pool_form.elements['pool_id'].value);
  data.append('name', edit_pool_form.elements['name'].value);
  data.append('description', edit_pool_form.elements['description'].value);
  data.append('price', edit_pool_form.elements['price'].value);

  let xhr = new XMLHttpRequest();
  xhr.open("POST", "ajax/pools.php", true);

  xhr.onload = function () {
      var myModal = document.getElementById('edit-pool');
      var modal = bootstrap.Modal.getInstance(myModal);
      modal.hide();

      // Log the response for debugging
      console.log("Response:", this.responseText);

      if (this.responseText == 1) {
          alert('success', 'Pool data updated!');
          edit_pool_form.reset();
          get_all_pools();
      } else {
          alert('error', 'Update Failed!');
      }
  };

  xhr.onerror = function () {
      console.error("Request failed");
      alert('error', 'An error occurred while updating the pool data.');
  };

  xhr.send(data);
}

function toggleStatus(id, val) {
  let xhr = new XMLHttpRequest();
  xhr.open("POST", "ajax/pools.php", true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

  xhr.onload = function () {
    if (this.responseText == 1) {
      alert('success', 'Status toggled!');
      get_all_pools();
    } else {
      alert('error', 'Status toggle failed!');
    }
  }

  xhr.send('toggle_status=' + id + '&value=' + val);
}

function remove_pool(id)
{
  if(confirm("Are you sure, you want to delete this pool?"))
  {
    let data = new FormData();
    data.append('pool_id',id);
    data.append('remove_pool','');

    let xhr = new XMLHttpRequest();
    xhr.open("POST","ajax/pools.php",true);

    xhr.onload = function()
    {
      if(this.responseText == 1){
        alert('success','Pool Removed!');
        get_all_pools();
      }
      else{
        alert('error','Pool removal failed!');
      }
    }
    xhr.send(data);
  }

}

window.onload = function(){
  get_all_pools();
}