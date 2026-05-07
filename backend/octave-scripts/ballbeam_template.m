pkg load control;

m = {{m}};          % hmotnosť guličky [kg]
R = {{R}};          % polomer guličky [m]
g = {{g}};          % gravitácia (kladná, napr. 9.8)
J = {{J}};          % moment zotrvačnosti [kg*m^2]
L = {{L}};          % dĺžka tyče [m] (len pre animáciu)

r0 = {{r0}};
rdot0 = {{rdot0}};
alpha0 = {{alpha0}};
alphadot0 = {{alphadot0}};

ref1 = {{ref1}};
ref2 = {{ref2}};

T = {{T}};
dt = {{dt}};

H = m*g / (J/(R^2) + m);
A = [0 1 0 0;
     0 0 H 0;
     0 0 0 1;
     0 0 0 0];
B = [0; 0; 0; 1];
C = [1 0 0 0];
D = 0;

K = place(A, B, [-2+2i, -2-2i, -20, -80]);
N = -inv(C * inv(A - B*K) * B);

sys_cl = ss(A - B*K, B*N, C, D);

t = (0:dt:T)';
x0 = [r0; rdot0; alpha0; alphadot0];

[y1, t1, x1] = lsim(sys_cl, ref1*ones(size(t)), t, x0);
[y2, t2, x2] = lsim(sys_cl, ref2*ones(size(t)), t, x1(end,:));

t_total = [t1; t2 + t1(end)];
x_total = [x1; x2];
y_total = [y1; y2];        

r = y_total;              
alpha = x_total(:,3);     


ball_x = r .* cos(alpha);
ball_y = r .* sin(alpha);   

fprintf('time,r,alpha,ball_x,ball_y\n');
for i = 1:length(t_total)
    fprintf('%f,%f,%f,%f,%f\n', t_total(i), r(i), alpha(i), ball_x(i), ball_y(i));
end