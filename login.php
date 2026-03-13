<!-- Example CSS for layout -->

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
.form-container {
  max-width: 600px;
  margin: 2rem auto;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.form-header {
  background-color: #1a365d;
  color: white;
  padding: 1.5rem;
  border-radius: 8px 8px 0 0;
  text-align: center;
}

.form-header h1 {
  margin: 0;
  font-size: 1.5rem;
}

.form-body {
  background-color: #fff;
  padding: 2rem;
  border-radius: 0 0 8px 8px;
}

.form-field {
  margin-bottom: 1.5rem;
}

.form-label {
  display: block;
  margin-bottom: 0.5rem;
  color: #2d3748;
  font-weight: 600;
}

.form-input, .form-select {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 4px;
  font-size: 1rem;
  transition: border-color 0.2s ease;
}

.form-input:focus, .form-select:focus {
  outline: none;
  border-color: #1a365d;
  box-shadow: 0 0 0 2px rgba(26, 54, 93, 0.1);
}

.form-button {
  width: 100%;
  padding: 0.75rem;
  background-color: #1a365d;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 1rem;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.form-button:hover {
  background-color: #1a365d;
}