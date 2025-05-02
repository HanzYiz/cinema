<?php

function showDisruptiveAlert($type, $title, $message) {
  $icons = [
    'success' => 'fa-circle-check',
    'danger'  => 'fa-circle-xmark',
    'warning' => 'fa-triangle-exclamation',
    'info'    => 'fa-circle-info'
  ];
  
  $colors = [
    'success' => '#22c55e',
    'danger'  => '#ef4444',
    'warning' => '#f59e0b',
    'info'    => '#3b82f6'
  ];

  return <<<HTML
  <div class="disruptive-alert disruptive-alert-{$type}" 
       style="--alert-color: {$colors[$type]}">
    <div class="alert-icon">
      <i class="fas {$icons[$type]}"></i>
    </div>
    <div class="alert-content">
      <h4>{$title}</h4>
      <p>{$message}</p>
    </div>
    <div class="alert-progress"></div>
  </div>
  <style>
    .disruptive-alert {
      position: fixed;
      top: 20px;
      right: 20px;
      width: 350px;
      background: white;
      border-radius: 12px;
      padding: 15px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
      display: flex;
      align-items: center;
      gap: 15px;
      transform: translateX(120%);
      transition: all 0.4s cubic-bezier(0.68, -0.55, 0.27, 1.55);
      z-index: 9999;
      border-left: 4px solid var(--alert-color);
    }
    
    .disruptive-alert.show {
      transform: translateX(0);
    }
    
    .alert-icon {
      font-size: 28px;
      color: var(--alert-color);
    }
    
    .alert-content h4 {
      margin: 0;
      color: #1f2937;
      font-weight: 600;
    }
    
    .alert-content p {
      margin: 5px 0 0;
      color: #6b7280;
      font-size: 14px;
    }
    
    .alert-progress {
      position: absolute;
      bottom: 0;
      left: 0;
      height: 3px;
      width: 100%;
      background: rgba(0,0,0,0.05);
    }
    
    .alert-progress:before {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      height: 100%;
      width: 100%;
      background: var(--alert-color);
      animation: progress 3s linear forwards;
    }
    
    @keyframes progress {
      100% { width: 0; }
    }
  </style>
  <script>
    setTimeout(() => {
  const alert = document.querySelector('.disruptive-alert');
  if (alert) {
    alert.classList.add('show');
    setTimeout(() => {
      alert.classList.remove('show');
      setTimeout(() => alert.remove(), 400);
    }, 3000);
  }
}, 100);
  </script>
HTML;
}
?>