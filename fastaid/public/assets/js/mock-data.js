/* Mock Data */
const MOCK_USERS = [
  { id: 1, name: "Sarah Miller", role: "patient", email: "sarah@test.com", verified: true, avatarInitials: "SM", avatarColor: "#C0392B" },
  { id: 2, name: "Dr. James Park", role: "volunteer", email: "james@test.com", verified: true, certification: "EMT-B", certNumber: "CERT-1234", yearsExp: 7, avatarInitials: "JP", avatarColor: "#2a7a2a", available: true, dndStart: "00:00", dndEnd: "00:00" },
  { id: 3, name: "Maria Gomez", role: "volunteer", email: "maria@test.com", verified: false, certification: "CPR", certNumber: "CERT-5678", yearsExp: 3, avatarInitials: "MG", avatarColor: "#8E2430", available: false, dndStart: "22:00", dndEnd: "07:00" },
  { id: 4, name: "David Chen", role: "volunteer", email: "david@test.com", verified: true, certification: "First Aid", certNumber: "CERT-9999", yearsExp: 5, avatarInitials: "DC", avatarColor: "#F4C430", available: true, dndStart: "00:00", dndEnd: "00:00" },
];

const MOCK_REQUESTS = [
  { id: "r101", patientId: 1, patientName: "Sarah Miller", type: "Bleeding", description: "Deep cut on forearm, bleeding steadily.", status: "Accepted", volunteerId: 2, volunteerName: "Dr. James Park", distanceKm: 1.2, startedAt: new Date(Date.now() - 3600000).toISOString() },
  { id: "r102", patientId: 1, patientName: "Sarah Miller", type: "First Aid", description: "Sprained ankle after fall.", status: "Arrived", volunteerId: 4, volunteerName: "David Chen", distanceKm: 0.8, startedAt: new Date(Date.now() - 7200000).toISOString() },
  { id: "r103", patientId: 1, patientName: "Sarah Miller", type: "Choking", description: "Adult choking on food, conscious.", status: "Requested", volunteerId: null, volunteerName: null, distanceKm: null, startedAt: new Date().toISOString() },
];

const MOCK_NOTIFICATIONS = [
  { id: 1, text: "New volunteer nearby accepted your request.", read: false, time: "2 min ago" },
  { id: 2, text: "Your case has been completed.", read: true, time: "1 hr ago" },
];

const MOCK_DEBRIEF = [
  { id: 1, text: "What worked well?", answer: "Quick response from nearby volunteer." },
  { id: 2, text: "What was hard?", answer: "Finding exact address in apartment complex." },
  { id: 3, text: "What would you change?", answer: "More pre-arrival instructions." },
];
