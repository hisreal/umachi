(function () {
    'use strict';

    const page = document.querySelector('.admin-dashboard-page');
    const dateTimeTarget = document.getElementById('adminLiveDateTime');
    const markReadButton = document.getElementById('markNotificationsRead');

    const showAlert = (icon, title, text) => {
        if (window.Swal) {
            window.Swal.fire({
                icon,
                title,
                text,
                confirmButtonColor: '#f68b34',
            });
            return;
        }

        window.alert(`${title}\n${text}`);
    };

    const updateDateTime = () => {
        if (!dateTimeTarget) {
            return;
        }

        const formatter = new Intl.DateTimeFormat('en-NG', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });

        dateTimeTarget.textContent = formatter.format(new Date());
    };

    const getChartData = () => {
        if (!page) {
            return null;
        }

        try {
            return JSON.parse(page.dataset.adminChartData || '{}');
        } catch (error) {
            return null;
        }
    };

    const setupCanvas = (canvas) => {
        if (!canvas) {
            return null;
        }

        const ratio = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();
        const width = rect.width || canvas.parentElement.clientWidth || 320;
        const height = Number(canvas.getAttribute('height')) || 230;
        canvas.width = width * ratio;
        canvas.height = height * ratio;
        canvas.style.height = `${height}px`;
        const context = canvas.getContext('2d');
        context.scale(ratio, ratio);
        return { context, width, height };
    };

    const drawText = (context, text, x, y, options = {}) => {
        context.fillStyle = options.color || '#667085';
        context.font = options.font || '700 11px Arial';
        context.textAlign = options.align || 'center';
        context.fillText(text, x, y);
    };

    const drawLineChart = (canvas, data) => {
        const canvasState = setupCanvas(canvas);
        if (!canvasState || !data) {
            return;
        }

        const { context, width, height } = canvasState;
        const padding = 34;
        const max = 100;
        const min = 85;
        const points = data.values.map((value, index) => {
            const x = padding + (index * ((width - padding * 2) / (data.values.length - 1)));
            const y = padding + ((max - value) / (max - min)) * (height - padding * 2);
            return { x, y, value, label: data.labels[index] };
        });

        context.clearRect(0, 0, width, height);
        context.strokeStyle = '#e5e7eb';
        context.lineWidth = 1;
        for (let index = 0; index < 4; index += 1) {
            const y = padding + index * ((height - padding * 2) / 3);
            context.beginPath();
            context.moveTo(padding, y);
            context.lineTo(width - padding, y);
            context.stroke();
        }

        context.strokeStyle = '#f68b34';
        context.lineWidth = 3;
        context.beginPath();
        points.forEach((point, index) => {
            if (index === 0) {
                context.moveTo(point.x, point.y);
            } else {
                context.lineTo(point.x, point.y);
            }
        });
        context.stroke();

        points.forEach((point) => {
            context.beginPath();
            context.fillStyle = '#ffffff';
            context.arc(point.x, point.y, 6, 0, Math.PI * 2);
            context.fill();
            context.strokeStyle = '#ed3237';
            context.lineWidth = 3;
            context.stroke();
            drawText(context, `${point.value}%`, point.x, point.y - 12, { color: '#101828', font: '800 11px Arial' });
            drawText(context, point.label, point.x, height - 10);
        });
    };


    const roundedRect = (context, x, y, width, height, radius) => {
        const safeRadius = Math.min(radius, width / 2, height / 2);
        context.beginPath();
        context.moveTo(x + safeRadius, y);
        context.lineTo(x + width - safeRadius, y);
        context.quadraticCurveTo(x + width, y, x + width, y + safeRadius);
        context.lineTo(x + width, y + height - safeRadius);
        context.quadraticCurveTo(x + width, y + height, x + width - safeRadius, y + height);
        context.lineTo(x + safeRadius, y + height);
        context.quadraticCurveTo(x, y + height, x, y + height - safeRadius);
        context.lineTo(x, y + safeRadius);
        context.quadraticCurveTo(x, y, x + safeRadius, y);
        context.closePath();
    };
    const drawBarChart = (canvas, data) => {
        const canvasState = setupCanvas(canvas);
        if (!canvasState || !data) {
            return;
        }

        const { context, width, height } = canvasState;
        const padding = 34;
        const max = Math.max(...data.values);
        const barArea = width - padding * 2;
        const barWidth = Math.max(18, barArea / data.values.length - 14);

        context.clearRect(0, 0, width, height);
        data.values.forEach((value, index) => {
            const x = padding + index * (barArea / data.values.length) + 7;
            const barHeight = (value / max) * (height - padding * 2);
            const y = height - padding - barHeight;
            const gradient = context.createLinearGradient(0, y, 0, height - padding);
            gradient.addColorStop(0, '#f68b34');
            gradient.addColorStop(1, '#ed3237');
            context.fillStyle = gradient;
            context.beginPath();
            context.roundRect(x, y, barWidth, barHeight, 8);
            context.fill();
            drawText(context, data.labels[index].slice(0, 3), x + barWidth / 2, height - 10);
        });
    };

    const drawDoughnutChart = (canvas, data) => {
        const canvasState = setupCanvas(canvas);
        if (!canvasState || !data) {
            return;
        }

        const { context, width, height } = canvasState;
        const colors = ['#f68b34', '#ed3237', '#0ea5e9', '#16a34a', '#7c3aed'];
        const total = data.values.reduce((sum, value) => sum + value, 0);
        const centerX = width / 2;
        const centerY = height / 2 - 8;
        const radius = Math.min(width, height) / 3.2;
        let startAngle = -Math.PI / 2;

        context.clearRect(0, 0, width, height);
        data.values.forEach((value, index) => {
            const sliceAngle = (value / total) * Math.PI * 2;
            context.beginPath();
            context.moveTo(centerX, centerY);
            context.fillStyle = colors[index % colors.length];
            context.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
            context.closePath();
            context.fill();
            startAngle += sliceAngle;
        });

        context.beginPath();
        context.fillStyle = '#ffffff';
        context.arc(centerX, centerY, radius * 0.55, 0, Math.PI * 2);
        context.fill();
        drawText(context, 'Leave', centerX, centerY - 4, { color: '#101828', font: '900 16px Arial' });
        drawText(context, 'Stats', centerX, centerY + 14, { color: '#667085', font: '800 12px Arial' });

        const legendY = height - 18;
        data.labels.slice(0, 3).forEach((label, index) => {
            const x = 34 + index * ((width - 68) / 3);
            context.fillStyle = colors[index];
            context.fillRect(x - 18, legendY - 8, 9, 9);
            drawText(context, label.split(' ')[0], x + 18, legendY, { align: 'left' });
        });
    };

    const drawCharts = () => {
        const chartData = getChartData();
        if (!chartData) {
            return;
        }

        drawLineChart(document.getElementById('attendanceChart'), chartData.attendance);
        drawBarChart(document.getElementById('salesChart'), chartData.sales);
        drawDoughnutChart(document.getElementById('leaveChart'), chartData.leave);
    };

    document.addEventListener('click', (event) => {
        const logoutLink = event.target.closest('[data-admin-logout="true"]');
        if (!logoutLink) {
            return;
        }

        event.preventDefault();

        if (window.Swal) {
            window.Swal.fire({
                icon: 'question',
                title: 'Are you sure you want to log out?',
                text: 'This is a frontend-only demo confirmation.',
                showCancelButton: true,
                confirmButtonText: 'Yes, Logout',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#ed3237',
                cancelButtonColor: '#667085',
            }).then((result) => {
                if (result.isConfirmed) {
                    window.Swal.fire({
                        icon: 'success',
                        title: 'Logout Confirmed (Demo Mode)',
                        text: 'Backend logout logic will be connected later.',
                        confirmButtonColor: '#f68b34',
                    });
                }
            });
            return;
        }

        window.alert('Logout confirmation will be connected later.');
    });
    document.addEventListener('click', (event) => {
        const leaveButton = event.target.closest('[data-leave-action]');
        if (!leaveButton) {
            return;
        }

        const action = leaveButton.dataset.leaveAction;
        const employee = leaveButton.dataset.employee;
        const title = action === 'approve' ? 'Leave Approved (Demo Mode)' : 'Leave Rejected (Demo Mode)';
        showAlert('success', title, `${employee}'s leave request would be ${action}d after backend integration.`);
    });

    if (markReadButton) {
        markReadButton.addEventListener('click', () => {
            document.querySelectorAll('.admin-notification-item.is-unread').forEach((item) => {
                item.classList.remove('is-unread');
                const badge = item.querySelector('.badge');
                if (badge) {
                    badge.remove();
                }
            });
            showAlert('success', 'Notifications Updated', 'All notifications have been marked as read in demo mode.');
        });
    }

    updateDateTime();
    window.setInterval(updateDateTime, 1000);
    drawCharts();
    window.addEventListener('resize', drawCharts);
}());
