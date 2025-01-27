module.exports = {
    apps: [
      {
        name: 'laravel-queue-worker',
        script: 'artisan',
        args: 'queue:work',
        interpreter: 'php',
        watch: false,
      }
    ]
  };
