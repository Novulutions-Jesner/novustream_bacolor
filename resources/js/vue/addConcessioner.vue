<template>
  <form ref="myForm"  @submit.prevent="saveConcessioner">
      <div class="row">
          <div class="col-md-12 mb-3">
              <div class="card shadow border-0">
                  <div class="card-header border-0 bg-primary bg-opacity-25">
                      <div class="text-uppercase fw-bold">Personal Information</div>
                  </div>
                  <div class="card-body">
                    <div class="row border-bottom">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Full Name <small class="text-danger"> ( required )</small></label>
                            <input type="text"
                                  class="form-control"
                                  id="name"
                                  v-model="concessioner.name"
                                  :class="{ 'is-invalid': errors && errors.name }"
                                  >
                            <small v-if="errors.name" class="text-danger px-1">{{ errors.name[0] }}</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="contact_no" class="form-label">Contact No <small class="text-danger"> ( required )</small></label>
                            <input type="text"
                                  class="form-control"
                                  id="contact_no"
                                  v-model="concessioner.contact_no"
                                  :class="{ 'is-invalid': errors && errors.contact_no }"
                                  >
                            <small v-if="errors.contact_no" class="text-danger px-1">{{ errors.contact_no[0] }}</small>
                        </div>
                    </div>
                    <div class="text-uppercase fw-bold py-4">Login Information</div>
                    <div class="row mt-3">
                        <div class="col-md-4 mb-3">
                            <label for="email" class="form-label">Email <small class="text-danger"> ( required )</small></label>
                            <input type="text"
                                    class="form-control" id="email"
                                    v-model="concessioner.email"
                                    :class="{ 'is-invalid': errors && errors.email }"
                                    >
                            <small v-if="errors.email" class="text-danger px-1">{{ errors.email[0] }}</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="password" class="form-label">Password <small class="text-danger"> ( required )</small></label>
                            <input type="password"
                                    class="form-control" id="password"
                                    v-model="concessioner.password"
                                    :class="{ 'is-invalid': errors && errors.password }"
                                    >
                            <small v-if="errors.password" class="text-danger px-1">{{ errors.password[0] }}</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="password_confirmation" class="form-label">Confirm Password <small class="text-danger"> ( required )</small></label>
                            <input type="password"
                                    class="form-control" id="password_confirmation"
                                    v-model="concessioner.password_confirmation"
                                    :class="{ 'is-invalid': errors && errors.password_confirmation }"
                                    >
                            <small v-if="errors.password_confirmation" class="text-danger px-1">{{ errors.password_confirmation[0] }}</small>
                        </div>
                    </div>
                </div>
              </div>
          </div>
      </div>
      <div class="card mt-3">
        <div class="card-header border-0 bg-primary bg-opacity-25 pb-3">
            <div class="text-uppercase fw-bold">Account Informations</div>
        </div>
        <div class="accordion accordion-flush border-5" v-for="(account, index) in concessioner.accounts" :key="index" id="accordionAccounts">
          <div class="accordion-item border-2 shadow">
            <h2 class="accordion-header d-flex align-items-center gap-3" :id="'flush-account-' + (index + 1)">
              <button
                class="accordion-button d-block text-uppercase fw-bold text-muted"
                :class="{ collapsed: index !== maxIndex }"
                type="button"
                data-bs-toggle="collapse"
                :data-bs-target="'#flush-account-collapse-' + (index + 1)"
                :aria-controls="'flush-account-collapse-' + (index + 1)">
                <div class="mb-1">
                  Account No: {{ account.account_no }}
                </div>
                <small class="text-muted">
                  {{ account.address }}
                </small>
              </button>
              <button type="button" v-if="index != 0" @click="removeAccount(index)" class="remove-account btn btn-danger mb-0 me-3"><i class="bx bx-trash"></i></button>
            </h2>
            <div
              :id="'flush-account-collapse-' + (index + 1)"
              class="accordion-collapse collapse"
              :aria-labelledby="'flush-account-' + (index + 1)"
              data-bs-parent="#accordionAccounts">
                <div class="p-3">
                  <div class="row border-bottom mb-4">
                    <div class="col-md-4 mb-3">
                        <label :for="'account_no_' + index" class="form-label">
                          Account No. <small class="text-danger">( required )</small>
                        </label>
                        <input type="text" class="form-control"
                                :id="'account_no_' + index"
                                v-model="account.account_no"
                                @input="updateZone(index)"
                                :class="{ 'is-invalid': errors && errors['accounts.' + index + '.account_no'] }"
                                >
                        <small v-if="errors['accounts.' + index + '.account_no']" class="text-danger px-1">{{ errors['accounts.' + index + '.account_no'][0] }}</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label :for="'property_type_' + index" class="form-label">
                          Property Type <small class="text-danger">( required )</small>
                        </label>
                        <select class="form-select"
                                :id="'property_type_' + index"
                                v-model="account.property_type"
                                :class="{ 'is-invalid': errors && errors['accounts.' + index + '.property_type'] }"
                                >
                          <option :value="null" disabled>-- SELECT --</option>
                          <option v-for="type in property_types" :key="type.id" :value="type.id">
                              {{ type.name.toUpperCase() }}
                          </option>
                        </select>
                        <small v-if="errors['accounts.' + index + '.property_type']" class="text-danger px-1">{{ errors['accounts.' + index + '.property_type'][0] }}</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label :for="'rate_code_' + index" class="form-label">
                          Rate Code <small class="text-danger">( required )</small>
                        </label>
                        <input type="number" class="form-control"
                              :id="'rate_code_' + index"
                              v-model="account.rate_code"
                              :class="{ 'is-invalid': errors && errors['accounts.' + index + '.rate_code'] }"
                              >
                        <small v-if="errors['accounts.' + index + '.rate_code']" class="text-danger px-1">{{ errors['accounts.' + index + '.rate_code'][0] }}</small>
                    </div>
                    <div class="col-md-12 mb-3">
                      <label :for="'senior_citizen_no_' + index" class="form-label">
                        Senior Citizen Discount <small class="text-danger"></small>
                      </label>
                      <input
                        type="text"
                        class="form-control"
                        :id="'senior_citizen_no_' + index"
                        :value="getScDiscountIdNo(index)"
                        :class="{ 'is-invalid': errors && errors['accounts.' + index + '.sc_discount.id_no'] }"
                      >
                      <small v-if="errors && errors['accounts.' + index + '.sc_discount.id_no']" class="text-danger px-1">
                        {{ errors['accounts.' + index + '.sc_discount.id_no'][0] }}
                      </small>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label :for="'address_' + index" class="form-label">
                          Address <small class="text-danger">( required )</small>
                        </label>
                        <input type="text" class="form-control"
                            :id="'address_' + index"
                            :value="account.address"
                            :class="{ 'is-invalid': errors && errors['accounts.' + index + '.address'] }" />
                        <small v-if="errors['accounts.' + index + '.address']" class="text-danger px-1">
                        {{ errors['accounts.' + index + '.address'][0] }}
                        </small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="isActive" class="form-label">
                            Status <small class="text-danger">( required )</small>
                        </label>
                        <select
                            class="form-select"
                            id="isActive"
                            v-model="account.status"
                            :class="{ 'is-invalid': errors && errors.isActive }"
                        >
                            <option :value="null" disabled>-- SELECT --</option>
                            <option value="AB">Active</option>
                            <option value="BL">Blocked</option>
                            <option value="ID">Inactive</option>
                            <option value="IV">Invalid</option>
                        </select>
                        <small v-if="errors.isActive" class="text-danger px-1">
                            {{ errors.isActive[0] }}
                        </small>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label :for="'meter_serial_no_' + index" class="form-label">
                          Meter Serial No <small class="text-danger">( required )</small>
                        </label>
                        <input type="text" class="form-control"
                              :id="'meter_serial_no_' + index"
                              v-model="account.meter_serial_no"
                              :class="{ 'is-invalid': errors && errors['accounts.' + index + '.meter_serial_no'] }"
                              >
                        <small v-if="errors['accounts.' + index + '.meter_serial_no']" class="text-danger px-1">{{ errors['accounts.' + index + '.meter_serial_no'][0] }}</small>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label :for="'sc_no_' + index" class="form-label">
                          SC No <small class="text-danger">( required )</small>
                        </label>
                        <input type="text" class="form-control"
                              :id="'sc_no_' + index"
                              v-model="account.sc_no"
                              :class="{ 'is-invalid': errors && errors['accounts.' + index + '.sc_no'] }"
                              >
                        <small v-if="errors['accounts.' + index + '.sc_no']" class="text-danger px-1">{{ errors['accounts.' + index + '.sc_no'][0] }}</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label :for="'date_connected_' + index" class="form-label">
                          Date Connected <small class="text-danger">( required )</small>
                        </label>
                        <input type="date" class="form-control"
                              :id="'date_connected_' + index"
                              v-model="account.date_connected"
                              :class="{ 'is-invalid': errors && errors['accounts.' + index + '.date_connected'] }"
                              >
                        <small v-if="errors['accounts.' + index + '.date_connected']" class="text-danger px-1">{{ errors['accounts.' + index + '.date_connected'][0] }}</small>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label :for="'sequence_no_' + index" class="form-label">
                          Sequence No <small class="text-danger">( required )</small>
                        </label>
                        <input type="text" class="form-control"
                              :id="'sequence_no_' + index"
                              v-model="account.sequence_no"
                              :class="{ 'is-invalid': errors && errors['accounts.' + index + '.sequence_no'] }"
                              >
                        <small v-if="errors['accounts.' + index + '.sequence_no']" class="text-danger px-1">{{ errors['accounts.' + index + '.sequence_no'][0] }}</small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label :for="'meter_brand_' + index" class="form-label">
                          Meter Brand
                        </label>
                        <input type="text" class="form-control"
                                :id="'meter_brand_' + index"
                                v-model="account.meter_brand"
                                :class="{ 'is-invalid': errors && errors['accounts.' + index + '.meter_brand'] }"
                                >
                        <small v-if="errors['accounts.' + index + '.meter_brand']" class="text-danger px-1">{{ errors['accounts.' + index + '.meter_brand'][0] }}</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label :for="'meter_type_' + index" class="form-label">
                          Meter Type
                        </label>
                        <input type="text" class="form-control"
                              :id="'meter_type_' + index"
                              v-model="account.meter_type"
                              :class="{ 'is-invalid': errors && errors['accounts.' + index + '.meter_type'] }"
                              >
                        <small v-if="errors['accounts.' + index + '.meter_type']" class="text-danger px-1">{{ errors['accounts.' + index + '.meter_type'][0] }}</small>
                    </div>
                    <div class="col-md-7 mb-3">
                        <label :for="'meter_wire_' + index" class="form-label">
                          Meter Wire
                        </label>
                        <input type="text" class="form-control"
                                :id="'meter_wire_' + index"
                                v-model="account.meter_wire"
                                :class="{ 'is-invalid': errors && errors['accounts.' + index + '.meter_type'] }"
                                >
                    </div>
                    <div class="col-md-5 mb-3">
                        <label :for="'meter_form_' + index" class="form-label">
                          Meter Form
                        </label>
                        <input type="text" class="form-control"
                                  :id="'meter_form_' + index"
                                  v-model="account.meter_form"
                                  :class="{ 'is-invalid': errors && errors['accounts.' + index + '.meter_form'] }"
                                  >
                        <small v-if="errors['accounts.' + index + '.meter_type']" class="text-danger px-1">{{ errors['accounts.' + index + '.meter_form'][0] }}</small>
                    </div>
                    <div class="col-md-5 mb-3">
                        <label :for="'meter_class_' + index" class="form-label">
                          Meter Class
                        </label>
                        <input type="text" class="form-control"
                                :id="'meter_class_' + index"
                                v-model="account.meter_class"
                                :class="{ 'is-invalid': errors && errors['accounts.' + index + '.meter_class'] }"
                                >
                        <small v-if="errors['accounts.' + index + '.meter_class']" class="text-danger px-1">{{ errors['accounts.' + index + '.meter_class'][0] }}</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label :for="'lat_long_' + index" class="form-label">
                          Latitude/Longitude
                        </label>
                        <input type="text" class="form-control"
                                :id="'lat_long_' + index"
                                v-model="account.lat_long"
                                :class="{ 'is-invalid': errors && errors['accounts.' + index + '.lat_long'] }"
                                >
                        <small v-if="errors['accounts.' + index + '.lat_long']" class="text-danger px-1">{{ errors['accounts.' + index + '.lat_long'][0] }}</small>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label :for="'isErcSealed_' + index" class="form-label">
                          ERC Sealed
                        </label>
                        <select class="form-select"
                                :id="'isErcSealed_' + index"
                                v-model="account.isErcSealed"
                                :class="{ 'is-invalid': errors && errors['accounts.' + index + '.isErcSealed'] }"
                                >
                          <option :value="null">-- SELECT --</option>
                          <option :value="1">Yes</option>
                          <option :value="0">No</option>
                        </select>
                        <small v-if="errors['accounts.' + index + '.isErcSealed']" class="text-danger px-1">{{ errors['accounts.' + index + '.isErcSealed'][0] }}</small>
                    </div>
                    <div class="col-md-12 mb-3">
                      <label :for="'inspectionImage_' + index" class="form-label">
                        Upload Inspection Image
                      </label>
                      <input
                        type="file"
                        class="form-control"
                        :id="'inspectionImage_' + index"
                        @change="handleFileUpload($event, index)"
                        :class="{ 'is-invalid': errors && errors['accounts.' + index + '.inspectionImage'] }"
                      />
                      <small v-if="errors['accounts.' + index + '.inspectionImage']" class="text-danger px-1">
                        {{ errors['accounts.' + index + '.inspectionImage'][0] }}
                      </small>
                    </div>
                    <div v-if="account.inspection_image" class="col-md-12 mb-3">
                      <label :for="'inspectedImage' + index" class="form-label">
                        Inspection Image
                      </label>
                      <div class="card shadow mt-2">
                        <div class="card-body">
                          <div v-if="account.inspection_image" class="col-md-12 mb-3">
                            <div class="lightgallery" :id="'lightgallery-' + index">
                              <a :href="getImageSrc(account)">
                                <img
                                  :src="getImageSrc(account)"
                                  alt="Inspection Preview"
                                  class="w-100 mt-2 image-inspected"
                                />
                              </a>
                            </div>
                          </div>
                        </div>
                      </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="d-flex py-3 gap-3 justify-content-end mt-5 pb-5">
        <button type="button" class="btn btn-secondary px-5 py-3 text-uppercase" :disabled="loading" @click="addAccount">Add Account</button>
        <button type="submit" class="btn btn-primary px-5 py-3 text-uppercase d-flex align-items-center gap-2" :disabled="loading">
          Submit
          <div class="spinner-border spinner-border-sm" role="status" v-if="loading">
            <span class="visually-hidden">Loading...</span>
          </div>
        </button>
      </div>
  </form>
</template>

<script>

import { nextTick } from 'vue';
const baseURL = window.location.origin; // http://127.0.0.1:8000


export default {
  mounted() {
    this.lightGallery();
  },
  props: {
    client: {
      type: String,
      required: true
    },
    property_types: {
      type: Array,
      required: true,
    },
    status_code: {
      type: Array,
      required: true,
    },
    data: {
      type: Object,
      required: false,
      default: () => null,
    },
  },
  data() {
    return {
      loading: false,
      concessioner: {
        name: '',
        contact_no: '',
        email: '',
        password: '',
        confirm_password: '',
        isActive: 1,
        accounts: [
          {
            account_no: '',
            sc_discount: {
              id_no: ''
            },
            zone: '',
            property_type: '',
            address: '',
            rate_code: '',
            status: '',
            meter_brand: '',
            meter_serial_no: '',
            sc_no: '',
            date_connected: '',
            sequence_no: '',
            meter_type: '',
            meter_wire: '',
            meter_form: '',
            meter_class: '',
            lat_long: '',
            isErcSealed: '',
            inspectionImage: '',
          },
        ],
      },
      errors: [],
      maxIndex: 0,
    };
  },
  created() {
    if (this.data) {
      this.concessioner = {
        ...this.concessioner,
        ...this.data,
        accounts: this.data.accounts ?? this.concessioner.accounts,
      };

      console.log(this.concessioner);
    }
  },
  methods: {
    getScDiscountIdNo(index) {
      const account = this.concessioner.accounts[index];
      return account && account.sc_discount ? account.sc_discount.id_no : '';
    },
    lightGallery() {
      nextTick(() => {
        document.querySelectorAll('.lightgallery').forEach((gallery) => {
          lightGallery(gallery, {
            plugins: [lgZoom, lgThumbnail],
            speed: 500,
          });
        });
      });
    },
    getZoneFromAccountNo(accountNo) {
  if (!accountNo) return '';
  const zone = accountNo.substring(0, 3);
  return /^\d{3}$/.test(zone) ? zone : '';
},
    updateZone(index) {
        for (const key in account) {
            const value = account[key];
            formData.append(`accounts[${index}][${key}]`, value ?? '');
        }
    },
    uppercaseAddress(event, index) {
        const input = event.target.value.toUpperCase();
        this.concessioner.accounts[index].address = input;
    },
    getImageSrc(account) {
      if (typeof account.inspection_image === 'string') {
        return '/storage/inspection_images/' + account.account_no + '/' + account.inspection_image;
      }
    },
    handleFileUpload(event, index) {
      const file = event.target.files[0];
      if (file) {
        this.concessioner.accounts[index].inspectionImage = file;
      }
    },
    addAccount() {
      this.concessioner.accounts.push({
        account_no: '',
        property_type: '',
        address: '',
        rate_code: '',
        status: '',
        meter_brand: '',
        meter_serial_no: '',
        sc_no: '',
        date_connected: '',
        sequence_no: '',
        meter_type: '',
        meter_wire: '',
        meter_form: '',
        meter_class: '',
        lat_long: '',
        isErcSealed: true,
        inspectionImage: '',
      });
      this.maxIndex = this.concessioner.accounts.length - 1;
    },
    saveConcessioner() {
        this.loading = true;
        this.errors = [];

        this.concessioner.accounts.forEach((account, index) => {
        account.zone = account.account_no ? account.account_no.substring(0, 3) : '';
        });

        let method = 'post';
        let endpoint = `${baseURL}/admin/users/concessionaires`;
        let data = { ...this.concessioner };

        if (this.concessioner.id) {
            endpoint = `${baseURL}/admin/users/concessionaires/${this.concessioner.id}`;
            data._method = 'PUT'; // method spoofing
        }


      const formData = new FormData();

      // Append top-level fields
      for (const key in this.concessioner) {
        if (key !== 'accounts') {
          formData.append(key, this.concessioner[key]);
        }
      }

      // Append nested accounts
      this.concessioner.accounts.forEach((account, index) => {
        for (const key in account) {
          const value = account[key];
          if (key === 'inspectionImage' && value instanceof File) {
            formData.append(`accounts[${index}][${key}]`, value);
          } else {
            formData.append(`accounts[${index}][${key}]`, value ?? '');
          }
        }
      });

      if (this.concessioner.id != null) {
        formData.append('_method', 'PUT');
      }

      axios({
        method: method,
        url: endpoint,
        data: formData,
        headers: {
          'X-CSRF-TOKEN': document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute('content'),
          'Content-Type': 'multipart/form-data',
        },
      })
        .then((response) => {
          if (this.concessioner.id == null) {
            this.resetForm();
          }
          alert(response.data.status, response.data.message);
        })
        .catch((error) => {
          if (error.response && error.response.status === 422) {
            const errors = error.response.data.errors
            this.errors = errors;
            if (errors.accounts) {
              alert('error', errors.accounts[0]);
            }
          } else {
            alert(error.status, error.message);
          }
        })
        .finally(() => {
          this.loading = false;
        });
    },
    removeAccount(index) {
      this.concessioner.accounts.splice(index, 1);
    },
    resetForm() {
      this.concessioner = {
        name: '',
        senior_citizen_no: '',
        contact_no: '',
        email: '',
        password: '',
        confirm_password: '',
        accounts: [
          {
            account_no: '',
            property_type: '',
            address: '',
            rate_code: '',
            status: '',
            meter_brand: '',
            meter_serial_no: '',
            sc_no: '',
            date_connected: '',
            sequence_no: '',
            meter_type: '',
            meter_wire: '',
            meter_form: '',
            meter_class: '',
            lat_long: '',
            isErcSealed: '',
            inspectionImage: '',
          },
        ],
      };
    },
  },
};

</script>

<style>
  img.image-inspected {
    width: 100%;
    height: 500px;
    overflow-y: scroll;
    object-fit: cover;
    cursor: pointer;
  }

  .remove-account {
    position: absolute;
    right: 0;
    z-index: 999;
  }

</style>
