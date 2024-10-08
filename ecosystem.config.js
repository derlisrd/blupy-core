module.exports = {
    apps: [
      {
        name: 'laravel-queue-worker',
        script: 'artisan',
        args: 'queue:work --tries=3',
        interpreter: 'php',
        watch: false,
      }
    ]
  };
