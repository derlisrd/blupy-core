module.exports = {
    apps: [
      {
        name: 'laravel-queue-worker',
        script: 'artisan',
        args: 'queue:work --sleep=3 --tries=3',
        interpreter: 'php',
        watch: false,
        autorestart: true,
        max_restarts: 10,
        restart_delay: 5000,
        env: {
          QUEUE_CONNECTION: 'database',
        },
      },
      {
        name: 'laravel-job-scheduler',
        script: 'artisan',
        args: 'schedule:run',
        interpreter: 'php',
        watch: false,
        autorestart: true,
        max_restarts: 10,
        restart_delay: 5000,
        cron_restart: '0 3 * * *',
        env: {
          QUEUE_CONNECTION: 'database',
        },
      },
    ],
  };
