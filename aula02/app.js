// Import the functions you need from the SDKs you need
import { initializeApp } from "firebase/app";
// import { getAnalytics } from "firebase/analytics";
import { getDatabase, set, ref } from "firebase/database";
// TODO: Add SDKs for Firebase products that you want to use
// https://firebase.google.com/docs/web/setup#available-libraries

// Your web app's Firebase configuration
// For Firebase JS SDK v7.20.0 and later, measurementId is optional
const firebaseConfig = {
  apiKey: "AIzaSyBgrHnVRMqkJyklxZGycZWlh6CREZ89OCc",
  authDomain: "bd-avancado-6d346.firebaseapp.com",
  databaseURL: "https://bd-avancado-6d346-default-rtdb.firebaseio.com",
  projectId: "bd-avancado-6d346",
  storageBucket: "bd-avancado-6d346.firebasestorage.app",
  messagingSenderId: "149769125685",
  appId: "1:149769125685:web:5e2acac6fa33c4ec6d567c",
  measurementId: "G-RSD265RSZT",
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
//const analytics = getAnalytics(app);
const db = getDatabase();

let newUserId = 1;
let refNode = ref(db, `users/${newUserId}`);
let newUserData = { email: "fulano@ifsul.edu.br", username: "fulano" };
set(refNode, newUserData);
