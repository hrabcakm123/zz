pkg load control;
M = {{M}};
m = {{m}};
b = {{b}};
I = {{I}};
g = {{g}};
l = {{l}};
initAngle = {{initAngle}};
initPosition = {{initPosition}};
ref1 = {{ref1}};
ref2 = {{ref2}};
T = {{T}};
dt = {{dt}};

p = I*(M+m)+M*m*l^2;
A = [0 1 0 0; 0 -(I+m*l^2)*b/p (m^2*g*l^2)/p 0; 0 0 0 1; 0 -(m*l*b)/p m*g*l*(M+m)/p 0];
B = [0; (I+m*l^2)/p; 0; m*l/p];
C = [1 0 0 0; 0 0 1 0];
D = [0; 0];

K = lqr(A,B,C'*C,1);
Ac = A-B*K;
N = -inv(C(1,:)*inv(Ac)*B);

sys = ss(Ac, B*N, C, D);

t = (0:dt:T)';
x0 = [initPosition; 0; initAngle; 0];

[y1, t1, x1] = lsim(sys, ref1*ones(size(t)), t, x0);

[y2, t2, x2] = lsim(sys, ref2*ones(size(t)), t, x1(end,:));

t_total = [t1; t2 + t1(end)];
x_total = [x1; x2];
cart_x = x_total(:,1);
theta = x_total(:,3);

pendulum_x = cart_x + l*sin(theta);
pendulum_y = l*cos(theta);

fprintf('time,x,theta,pendulum_x,pendulum_y\n');
for i=1:length(t_total)
    fprintf('%f,%f,%f,%f,%f\n', t_total(i), cart_x(i), theta(i), pendulum_x(i), pendulum_y(i));
end