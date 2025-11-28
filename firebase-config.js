// Firebase Configuration
const firebaseConfig = {
  apiKey: "AIzaSyDPZY7B1BKzNrJRTulWFa0P0t28qlMDzig",
  authDomain: "onlinebizpermit.firebaseapp.com",
  projectId: "onlinebizpermit",
  storageBucket: "onlinebizpermit.firebasestorage.app",
  messagingSenderId: "37215767726",
  appId: "1:37215767726:web:44e68cd75b2628b438b13f",
  measurementId: "G-7RJHQKV7SC"
};

// Initialize Firebase
firebase.initializeApp(firebaseConfig);

// Initialize Firebase services
const auth = firebase.auth();
const db = firebase.firestore();
const storage = firebase.storage();
const analytics = firebase.analytics();
