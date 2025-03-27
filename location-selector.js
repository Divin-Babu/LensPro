// Improved Location Selector for Photographer Forms
class LocationSelector {
    constructor(stateSelectId, citySelectId) {
      this.stateSelect = document.getElementById(stateSelectId);
      this.citySelect = document.getElementById(citySelectId);
      this.states = [];
      this.cities = {};
      
      // Initialize the location selectors
      this.init();
    }
    
    // Initialize the component
    async init() {
      // Disable city select until state is selected
      this.citySelect.disabled = true;
      
      try {
        // Set up states directly
        this.setupStates();
        
        // Add event listener for state selection
        this.stateSelect.addEventListener('change', (e) => this.handleStateChange(e));
        
        // Set initial text
        this.stateSelect.innerHTML = '<option value="">Select State</option>' + 
          this.states.map(state => `<option value="${state}">${state}</option>`).join('');
      } catch (error) {
        console.error('Failed to initialize location selector:', error);
      }
    }
    
    // Set up states list
    setupStates() {
      this.states = [
        'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh', 
        'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka', 
        'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram', 
        'Nagaland', 'Odisha', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu', 
        'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal',
        'Andaman and Nicobar Islands', 'Chandigarh', 'Dadra and Nagar Haveli and Daman and Diu', 
        'Delhi', 'Jammu & Kashmir', 'Ladakh', 'Lakshadweep', 'Puducherry'
      ].sort();
      
      // Pre-define cities for states
      this.setupCities();
    }
    
    // Set up cities for all states
    setupCities() {
      this.cities = {
        'Andhra Pradesh': ['Visakhapatnam', 'Vijayawada', 'Guntur', 'Tirupati', 'Nellore', 'Kurnool', 'Kakinada', 'Rajahmundry', 'Anantapur', 'Kadapa'],
        'Arunachal Pradesh': ['Itanagar', 'Naharlagun', 'Pasighat', 'Namsai', 'Tezu', 'Aalo', 'Tawang', 'Ziro', 'Bomdila', 'Roing'],
        'Assam': ['Guwahati', 'Silchar', 'Dibrugarh', 'Jorhat', 'Nagaon', 'Tinsukia', 'Tezpur', 'Karimganj', 'Hailakandi', 'Goalpara'],
        'Bihar': ['Patna', 'Gaya', 'Bhagalpur', 'Muzaffarpur', 'Darbhanga', 'Arrah', 'Begusarai', 'Chhapra', 'Purnia', 'Katihar'],
        'Chhattisgarh': ['Raipur', 'Bhilai', 'Bilaspur', 'Korba', 'Durg', 'Raigarh', 'Rajnandgaon', 'Jagdalpur', 'Ambikapur', 'Dhamtari'],
        'Goa': ['Panaji', 'Margao', 'Vasco da Gama', 'Mapusa', 'Ponda', 'Bicholim', 'Curchorem', 'Sanguem', 'Pernem', 'Canacona'],
        'Gujarat': ['Ahmedabad', 'Surat', 'Vadodara', 'Rajkot', 'Bhavnagar', 'Jamnagar', 'Junagadh', 'Gandhinagar', 'Anand', 'Navsari'],
        'Haryana': ['Faridabad', 'Gurgaon', 'Panipat', 'Ambala', 'Yamunanagar', 'Rohtak', 'Hisar', 'Karnal', 'Sonipat', 'Panchkula'],
        'Himachal Pradesh': ['Shimla', 'Mandi', 'Dharamshala', 'Solan', 'Kullu', 'Palampur', 'Baddi', 'Nahan', 'Hamirpur', 'Una'],
        'Jharkhand': ['Ranchi', 'Jamshedpur', 'Dhanbad', 'Bokaro', 'Hazaribagh', 'Deoghar', 'Giridih', 'Ramgarh', 'Phusro', 'Chirkunda'],
        'Karnataka': ['Bangalore', 'Mysore', 'Hubli', 'Mangalore', 'Belgaum', 'Gulbarga', 'Davanagere', 'Bellary', 'Bijapur', 'Shimoga'],
        'Kerala': ['Thiruvananthapuram', 'Kochi', 'Kozhikode', 'Thrissur', 'Kollam', 'Palakkad', 'Alappuzha', 'Kannur', 'Kottayam', 'Kasaragod'],
        'Madhya Pradesh': ['Indore', 'Bhopal', 'Jabalpur', 'Gwalior', 'Ujjain', 'Sagar', 'Dewas', 'Satna', 'Ratlam', 'Rewa'],
        'Maharashtra': ['Mumbai', 'Pune', 'Nagpur', 'Thane', 'Nashik', 'Aurangabad', 'Solapur', 'Kolhapur', 'Amravati', 'Nanded'],
        'Manipur': ['Imphal', 'Thoubal', 'Bishnupur', 'Kakching', 'Ukhrul', 'Churachandpur', 'Senapati', 'Tamenglong', 'Chandel', 'Jiribam'],
        'Meghalaya': ['Shillong', 'Tura', 'Nongstoin', 'Jowai', 'Baghmara', 'Williamnagar', 'Resubelpara', 'Nongpoh', 'Khliehriat', 'Mawkyrwat'],
        'Mizoram': ['Aizawl', 'Lunglei', 'Saiha', 'Champhai', 'Kolasib', 'Serchhip', 'Lawngtlai', 'Mamit', 'Khawzawl', 'Hnahthial'],
        'Nagaland': ['Kohima', 'Dimapur', 'Mokokchung', 'Tuensang', 'Wokha', 'Zunheboto', 'Mon', 'Phek', 'Kiphire', 'Longleng'],
        'Odisha': ['Bhubaneswar', 'Cuttack', 'Rourkela', 'Berhampur', 'Sambalpur', 'Puri', 'Balasore', 'Bhadrak', 'Baripada', 'Jharsuguda'],
        'Punjab': ['Ludhiana', 'Amritsar', 'Jalandhar', 'Patiala', 'Bathinda', 'Mohali', 'Pathankot', 'Hoshiarpur', 'Batala', 'Moga'],
        'Rajasthan': ['Jaipur', 'Jodhpur', 'Udaipur', 'Kota', 'Bikaner', 'Ajmer', 'Bhilwara', 'Alwar', 'Sikar', 'Sri Ganganagar'],
        'Sikkim': ['Gangtok', 'Namchi', 'Mangan', 'Gyalshing', 'Rangpo', 'Singtam', 'Ravangla', 'Jorethang', 'Nayabazar', 'Pakyong'],
        'Tamil Nadu': ['Chennai', 'Coimbatore', 'Madurai', 'Tiruchirappalli', 'Salem', 'Tirunelveli', 'Erode', 'Vellore', 'Thoothukudi', 'Dindigul'],
        'Telangana': ['Hyderabad', 'Warangal', 'Nizamabad', 'Karimnagar', 'Khammam', 'Ramagundam', 'Mahbubnagar', 'Nalgonda', 'Adilabad', 'Suryapet'],
        'Tripura': ['Agartala', 'Udaipur', 'Dharmanagar', 'Kailasahar', 'Ambassa', 'Belonia', 'Khowai', 'Teliamura', 'Sonamura', 'Santirbazar'],
        'Uttar Pradesh': ['Lucknow', 'Kanpur', 'Ghaziabad', 'Agra', 'Varanasi', 'Meerut', 'Allahabad', 'Bareilly', 'Aligarh', 'Moradabad'],
        'Uttarakhand': ['Dehradun', 'Haridwar', 'Roorkee', 'Haldwani', 'Rudrapur', 'Kashipur', 'Rishikesh', 'Pithoragarh', 'Ramnagar', 'Mussoorie'],
        'West Bengal': ['Kolkata', 'Howrah', 'Durgapur', 'Asansol', 'Siliguri', 'Bardhaman', 'Malda', 'Baharampur', 'Kharagpur', 'Haldia'],
        'Andaman and Nicobar Islands': ['Port Blair', 'Mayabunder', 'Rangat', 'Diglipur', 'Bambooflat', 'Havelock Island', 'Car Nicobar', 'Little Andaman', 'Neil Island', 'Kamorta'],
        'Chandigarh': ['Chandigarh', 'Manimajra', 'Mohali', 'Panchkula'],
        'Dadra and Nagar Haveli and Daman and Diu': ['Silvassa', 'Daman', 'Diu', 'Dadra', 'Naroli', 'Vapi'],
        'Delhi': ['New Delhi', 'North Delhi', 'South Delhi', 'East Delhi', 'West Delhi', 'Central Delhi', 'Dwarka', 'Rohini', 'Janakpuri', 'Vasant Kunj'],
        'Jammu & Kashmir': ['Srinagar', 'Jammu', 'Anantnag', 'Baramulla', 'Udhampur', 'Kathua', 'Sopore', 'Pulwama', 'Kupwara', 'Poonch'],
        'Ladakh': ['Leh', 'Kargil', 'Diskit', 'Shey', 'Thiksey', 'Alchi', 'Zanskar', 'Drass', 'Turtuk', 'Nyoma'],
        'Lakshadweep': ['Kavaratti', 'Agatti', 'Amini', 'Andrott', 'Kalpeni', 'Kiltan', 'Minicoy', 'Kadmat', 'Chetlat', 'Bitra'],
        'Puducherry': ['Puducherry', 'Karaikal', 'Yanam', 'Mahe', 'Ozhukarai', 'Villianur', 'Ariyankuppam', 'Bahour', 'Mannadipet', 'Nettapakkam']
      };
    }
    
    // Get cities for a given state
    getCities(stateName) {
      return this.cities[stateName] || [];
    }
    
    // Handle state selection change
    async handleStateChange(event) {
      const selectedState = event.target.value;
      
      // Reset city dropdown
      this.citySelect.innerHTML = '<option value="">Loading cities...</option>';
      this.citySelect.disabled = true;
      
      if (selectedState) {
        try {
          // Get cities for selected state
          const cities = this.getCities(selectedState);
          
          // Populate city dropdown
          this.citySelect.innerHTML = '<option value="">Select City</option>' + 
            cities.map(city => `<option value="${city}">${city}</option>`).join('');
          
          // Enable city selection
          this.citySelect.disabled = false;
        } catch (error) {
          console.error('Failed to load cities:', error);
          this.citySelect.innerHTML = '<option value="">Error loading cities</option>';
        }
      } else {
        // Reset if no state selected
        this.citySelect.innerHTML = '<option value="">Select City</option>';
        this.citySelect.disabled = true;
      }
    }
    
    // Get the full location string (State, City)
    getLocationString() {
      const state = this.stateSelect.value;
      const city = this.citySelect.value;
      
      if (state && city) {
        return `${city}, ${state}`;
      } else if (state) {
        return state;
      }
      return '';
    }
  }