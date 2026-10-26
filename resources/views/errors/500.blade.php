@include('errors.layout', [
    'code' => 500,
    'title' => 'Erreur technique',
    'heading' => "Un problème technique nous empêche d'afficher *cette page*.",
    'message' => 'Notre équipe en est informée. Réessayez dans quelques minutes ou écrivez-nous directement sur WhatsApp.',
])
